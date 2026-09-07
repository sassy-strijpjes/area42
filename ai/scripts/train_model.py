"""Train SARIMAX occupancy model (weekly or daily) using baseline + residual decomposition.

1. Preprocess: add shifted lag features + Fourier calendar exog
2. Compute ISO-week baseline (with month/global fallback)
3. Subtract baseline → residuals
4. Train SARIMAX on residuals
5. Prediction = baseline + residual_prediction
"""
from __future__ import annotations

import argparse
import json
import warnings
from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.metrics import mean_absolute_error, mean_squared_error
from statsmodels.tsa.statespace.sarimax import SARIMAX

PROJECT_ROOT = Path(__file__).resolve().parent.parent
TARGET = "occupancy_rate"

CANDIDATE_ORDERS = [
    (1, 0, 0), (1, 0, 1), (2, 0, 0), (2, 0, 1), (1, 1, 0), (1, 1, 1),
]


def parse_args() -> argparse.Namespace:
    p = argparse.ArgumentParser(description="Train Area-42 occupancy prediction model.")
    p.add_argument("--granularity", choices=["weekly", "daily", "both"], default="both")
    p.add_argument("--weekly-input", default=str(PROJECT_ROOT / "Data/Processed/occupancy_weekly.csv"))
    p.add_argument("--daily-input", default=str(PROJECT_ROOT / "Data/Processed/occupancy_daily.csv"))
    return p.parse_args()


# ── Preprocessing ───────────────────────────────────────────────────

def is_holiday(day: pd.Timestamp) -> int:
    m, w = int(day.month), int(day.isocalendar().week)
    return int(m in (7, 8) or w in range(17, 20) or w in range(30, 36)
               or w in range(42, 44) or (m == 12 and day.day >= 20) or (m == 1 and day.day <= 5))


def preprocess_weekly(path: str) -> pd.DataFrame:
    df = pd.read_csv(path, parse_dates=["week_start"]).sort_values("week_start")
    df["iso_week"] = df["week_start"].dt.isocalendar()["week"].astype(int)
    df["month"] = df["week_start"].dt.month.astype(int)
    if "is_holiday_period" not in df.columns:
        df["is_holiday_period"] = df["week_start"].apply(is_holiday).astype(int)
    if "is_weekend" not in df.columns:
        df["is_weekend"] = 0
    df["previous_occupancy"] = df[TARGET].shift(1).fillna(0)
    df["rolling_4_week_occupancy"] = df[TARGET].shift(1).rolling(4, min_periods=1).mean().fillna(0)
    for c in ["known_reservations_30d_before", "known_guest_count_30d_before", "known_average_nights_30d_before"]:
        if c not in df.columns:
            df[c] = 42 if "reservations" in c else 42 * 3.4 if "guest" in c else 4.8
    wa = 2 * np.pi * df["iso_week"] / 52.0
    ma = 2 * np.pi * df["month"] / 12.0
    df["week_sin_1"], df["week_cos_1"] = np.sin(wa), np.cos(wa)
    df["week_sin_2"], df["week_cos_2"] = np.sin(2 * wa), np.cos(2 * wa)
    df["month_sin"], df["month_cos"] = np.sin(ma), np.cos(ma)
    return df


def preprocess_daily(path: str) -> pd.DataFrame:
    df = pd.read_csv(path, parse_dates=["stay_date"]).sort_values("stay_date")
    df["iso_week"] = df["stay_date"].dt.isocalendar()["week"].astype(int)
    df["month"] = df["stay_date"].dt.month.astype(int)
    df["day_of_week"] = df["stay_date"].dt.dayofweek.astype(int)
    if "is_holiday_period" not in df.columns:
        df["is_holiday_period"] = df["stay_date"].apply(is_holiday).astype(int)
    if "is_weekend" not in df.columns:
        df["is_weekend"] = df["day_of_week"].isin([5, 6]).astype(int)
    df["previous_occupancy"] = df[TARGET].shift(1).fillna(df[TARGET].iloc[0])
    df["rolling_7_day_occupancy"] = df[TARGET].shift(1).rolling(7, min_periods=1).mean()
    df["rolling_28_day_occupancy"] = df[TARGET].shift(1).rolling(28, min_periods=1).mean()
    for c in ["known_reservations_30d_before", "known_guest_count_30d_before", "known_average_nights_30d_before"]:
        if c not in df.columns:
            df[c] = 42 if "reservations" in c else 42 * 3.4 if "guest" in c else 4.8
    wa = 2 * np.pi * df["iso_week"] / 52.0
    ma = 2 * np.pi * df["month"] / 12.0
    da = 2 * np.pi * df["iso_week"] / 365.25
    df["day_sin_1"], df["day_cos_1"] = np.sin(da), np.cos(da)
    df["day_sin_2"], df["day_cos_2"] = np.sin(2 * da), np.cos(2 * da)
    df["week_sin_1"], df["week_cos_1"] = np.sin(wa), np.cos(wa)
    df["week_sin_2"], df["week_cos_2"] = np.sin(2 * wa), np.cos(2 * wa)
    df["month_sin"], df["month_cos"] = np.sin(ma), np.cos(ma)
    df = df.fillna(0)
    return df


# ── Utilities ───────────────────────────────────────────────────────

def chronological_split(data: pd.DataFrame, date_col: str) -> tuple[pd.DataFrame, pd.DataFrame]:
    data = data.sort_values(date_col).reset_index(drop=True)
    year_counts = data[date_col].dt.year.value_counts().sort_index()
    min_pp = 180 if date_col == "stay_date" else 26
    full_years = year_counts[year_counts >= min_pp].index.tolist()
    if len(full_years) >= 3:
        ty = int(full_years[-1])
        return data[data[date_col].dt.year < ty].copy(), data[data[date_col].dt.year >= ty].copy()
    s = int(len(data) * 0.8)
    return data.iloc[:s].copy(), data.iloc[s:].copy()


def smape(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    d = (np.abs(y_true) + np.abs(y_pred)) / 2
    return float(np.mean(np.where(d == 0, 0, np.abs(y_true - y_pred) / d)) * 100)


def metrics(y_true: pd.Series, y_pred: np.ndarray) -> dict:
    return {"mae": round(float(mean_absolute_error(y_true, y_pred)), 4),
            "rmse": round(float(np.sqrt(mean_squared_error(y_true, y_pred))), 4),
            "smape": round(smape(y_true.to_numpy(), np.asarray(y_pred)), 4)}


def crowd_level(v: float) -> str:
    return "laag" if v < 50 else "normaal" if v < 80 else "hoog"


def build_baseline(train: pd.DataFrame) -> dict:
    wa = train.groupby("iso_week")[TARGET].mean().to_dict()
    ma = train.groupby("month")[TARGET].mean().to_dict()
    ga = float(train[TARGET].mean())
    return {"strategy": "ISO week avg w/ month/global fallback",
            "week_average": {str(k): float(v) for k, v in wa.items()},
            "month_average": {str(k): float(v) for k, v in ma.items()},
            "global_average": ga}


def baseline_predict(data: pd.DataFrame, bl: dict) -> np.ndarray:
    wa = {int(k): v for k, v in bl["week_average"].items()}
    ma = {int(k): v for k, v in bl["month_average"].items()}
    ga = float(bl["global_average"])
    return np.array([wa.get(r.iso_week, ma.get(r.month, ga)) for r in data.itertuples(index=False)])


def fit_sarimax(endog: pd.Series, exog: pd.DataFrame, order: tuple):
    m = SARIMAX(endog=endog, exog=exog, order=order, seasonal_order=(0, 0, 0, 0),
                trend="c", enforce_stationarity=False, enforce_invertibility=False)
    return m.fit(disp=False, maxiter=500)


def select_model(train: pd.DataFrame, exog_cols: list[str]):
    best_result, best_order, candidates = None, None, []
    for order in CANDIDATE_ORDERS:
        try:
            with warnings.catch_warnings():
                warnings.simplefilter("ignore")
                r = fit_sarimax(train["baseline_residual"], train[exog_cols], order)
            candidates.append({"order": list(order), "aic": round(float(r.aic), 4),
                              "converged": bool(r.mle_retvals.get("converged", False))})
            if best_result is None or r.aic < best_result.aic:
                best_result, best_order = r, order
        except Exception as e:
            candidates.append({"order": list(order), "error": str(e)})
    if best_result is None:
        raise RuntimeError("No SARIMAX candidate converged.")
    return best_order, best_result, candidates


# ── Train one granularity ───────────────────────────────────────────

def train_granularity(name: str, input_path: str) -> dict:
    print(f"\n{'='*50}\n  {name.upper()}\n{'='*50}")

    is_weekly = name == "weekly"
    date_col = "week_start" if is_weekly else "stay_date"
    exog_cols = (["is_holiday_period", "week_sin_1", "week_cos_1", "week_sin_2", "week_cos_2", "month_sin", "month_cos"]
                 if is_weekly else
                 ["is_holiday_period", "day_sin_1", "day_cos_1", "day_sin_2", "day_cos_2",
                  "week_sin_1", "week_cos_1", "week_sin_2", "week_cos_2", "month_sin", "month_cos"])

    data = preprocess_weekly(input_path) if is_weekly else preprocess_daily(input_path)
    train, test = chronological_split(data, date_col)

    bl = build_baseline(train)
    train_bl = np.clip(baseline_predict(train, bl), 0, 100)
    test_bl = np.clip(baseline_predict(test, bl), 0, 100)
    train["baseline_residual"] = train[TARGET] - train_bl

    best_order, best_result, candidates = select_model(train, exog_cols)

    fc = best_result.get_forecast(steps=len(test), exog=test[exog_cols])
    res_pred = fc.predicted_mean.to_numpy()
    preds = np.clip(test_bl + res_pred, 0, 100)
    ci = fc.conf_int(alpha=0.2)
    lo = np.clip(test_bl + ci.iloc[:, 0].to_numpy(), 0, 100)
    hi = np.clip(test_bl + ci.iloc[:, 1].to_numpy(), 0, 100)
    bl_m = metrics(test[TARGET], test_bl)
    sar_m = metrics(test[TARGET], preds)
    interval_coverage = float(np.mean((test[TARGET].to_numpy() >= lo) & (test[TARGET].to_numpy() <= hi)))

    full_bl = build_baseline(data)
    full_baseline = np.clip(baseline_predict(data, full_bl), 0, 100)
    data["baseline_residual"] = data[TARGET] - full_baseline
    final_result = fit_sarimax(data["baseline_residual"], data[exog_cols], best_order)

    dest = PROJECT_ROOT / "Models" / f"occupancy_{name}.pkl"
    dest.parent.mkdir(parents=True, exist_ok=True)
    final_result.save(str(dest))

    meta = {"model_name": "seasonal_baseline_plus_sarimax_residual", "granularity": name,
            "exog_columns": exog_cols, "order": list(best_order),
            "baseline_model": {"strategy": full_bl["strategy"],
                               "week_average": {str(k): v for k, v in full_bl["week_average"].items()},
                               "month_average": {str(k): v for k, v in full_bl["month_average"].items()},
                               "global_average": full_bl["global_average"]},
            "clip_range": [0, 100]}
    meta_path = PROJECT_ROOT / "Models" / f"occupancy_{name}_metadata.json"
    with open(meta_path, "w") as f:
        json.dump(meta, f, indent=2)

    out = test[[date_col, TARGET]].copy().rename(columns={TARGET: "actual_occupancy"})
    out["predicted_occupancy"] = np.round(preds, 2)
    out["lower_bound"] = np.round(lo, 2)
    out["upper_bound"] = np.round(hi, 2)
    out["crowd_level"] = out["predicted_occupancy"].apply(crowd_level)
    pred_path = PROJECT_ROOT / "Reports" / f"predictions_{name}.csv"
    pred_path.parent.mkdir(parents=True, exist_ok=True)
    out.to_csv(pred_path, index=False)

    report = {
        "granularity": name,
        "train_rows": len(train),
        "test_rows": len(test),
        "baseline": bl_m,
        "sarimax": sar_m,
        # Flat fields keep the Laravel UI and existing report consumers simple.
        "baseline_mae": bl_m["mae"],
        "sarimax_mae": sar_m["mae"],
        "sarimax_rmse": sar_m["rmse"],
        "sarimax_smape": sar_m["smape"],
        "interval_coverage_80": round(interval_coverage, 4),
        "best_order": list(best_order),
    }
    report_path = PROJECT_ROOT / "Reports" / f"metrics_{name}.json"
    with open(report_path, "w") as f:
        json.dump(report, f, indent=2)

    print(f"  Train: {len(train)} | Test: {len(test)}")
    print(f"  Baseline MAE: {bl_m['mae']} | SARIMAX MAE: {sar_m['mae']}")
    print(f"  Model → {dest}")
    return report


def main() -> None:
    args = parse_args()
    if args.granularity in ("weekly", "both"):
        train_granularity("weekly", args.weekly_input)
    if args.granularity in ("daily", "both"):
        train_granularity("daily", args.daily_input)


if __name__ == "__main__":
    main()
