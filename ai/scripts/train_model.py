from __future__ import annotations

import argparse
import json
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from sklearn.ensemble import GradientBoostingRegressor, RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error

# Resolve paths relative to the ai/ folder (parent of the scripts/ folder)
PROJECT_ROOT = Path(__file__).resolve().parent.parent

TARGET_COLUMN = "occupancy_rate"

# ── Weekly feature columns ──────────────────────────────────────────
WEEKLY_FEATURE_COLUMNS = [
    "iso_week",
    "month",
    "season",
    "is_holiday_period",
    "is_weekend",
    "previous_occupancy",
    "rolling_4_week_occupancy",
    "known_reservations_30d_before",
    "known_guest_count_30d_before",
    "known_average_nights_30d_before",
]

# ── Daily feature columns ───────────────────────────────────────────
DAILY_FEATURE_COLUMNS = [
    "day_of_week",
    "month",
    "season",
    "is_holiday_period",
    "is_weekend",
    "previous_occupancy",
    "rolling_7_day_occupancy",
    "rolling_28_day_occupancy",
    "known_reservations_30d_before",
    "known_guest_count_30d_before",
    "known_average_nights_30d_before",
]


# ── CLI ─────────────────────────────────────────────────────────────

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Train Area-42 occupancy prediction model(s).")
    parser.add_argument(
        "--granularity",
        choices=["weekly", "daily", "both"],
        default="both",
        help="Which model(s) to train (default: both).",
    )
    parser.add_argument("--weekly-input", default=str(PROJECT_ROOT / "Data/Processed/occupancy_weekly.csv"))
    parser.add_argument("--daily-input", default=str(PROJECT_ROOT / "Data/Processed/occupancy_daily.csv"))
    parser.add_argument("--train-output", default=str(PROJECT_ROOT / "Data/Training"))
    parser.add_argument("--test-output", default=str(PROJECT_ROOT / "Data/Test"))
    parser.add_argument("--model-output", default=str(PROJECT_ROOT / "Models"))
    parser.add_argument("--report-output", default=str(PROJECT_ROOT / "Reports"))
    return parser.parse_args()


# ── Shared utilities ────────────────────────────────────────────────

def is_holiday_period(day: pd.Timestamp) -> int:
    month = int(day.month)
    iso_week = int(day.isocalendar().week)
    return int(
        month in (7, 8)
        or iso_week in range(17, 20)
        or iso_week in range(30, 36)
        or iso_week in range(42, 44)
        or (month == 12 and day.day >= 20)
        or (month == 1 and day.day <= 5)
    )


def season_from_month(month: int) -> int:
    if month in (12, 1, 2):
        return 0
    if month in (3, 4, 5):
        return 1
    if month in (6, 7, 8):
        return 2
    return 3


def smape(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    denominator = (np.abs(y_true) + np.abs(y_pred)) / 2
    values = np.where(denominator == 0, 0, np.abs(y_true - y_pred) / denominator)
    return float(np.mean(values) * 100)


def compute_metrics(y_true: pd.Series, y_pred: np.ndarray) -> dict[str, float]:
    return {
        "mae": round(float(mean_absolute_error(y_true, y_pred)), 4),
        "rmse": round(float(np.sqrt(mean_squared_error(y_true, y_pred))), 4),
        "smape": round(smape(y_true.to_numpy(), np.asarray(y_pred)), 4),
    }


def crowd_level(value: float) -> str:
    if value < 50:
        return "laag"
    if value < 80:
        return "normaal"
    return "hoog"


def feature_importance(model: object, feature_list: list[str]) -> list[dict[str, float]]:
    importances = getattr(model, "feature_importances_", None)
    if importances is None:
        return []
    rows = [
        {"feature": feature, "importance": round(float(importance), 6)}
        for feature, importance in zip(feature_list, importances)
    ]
    return sorted(rows, key=lambda row: row["importance"], reverse=True)


def train_candidates(x_train: pd.DataFrame, y_train: pd.Series) -> dict[str, object]:
    models = {
        "random_forest": RandomForestRegressor(
            n_estimators=250, max_depth=8, min_samples_leaf=2, random_state=42,
        ),
        "gradient_boosting": GradientBoostingRegressor(
            n_estimators=180, learning_rate=0.045, max_depth=3, random_state=42,
        ),
    }
    for model in models.values():
        model.fit(x_train, y_train)
    return models


# ── Weekly data pipeline ────────────────────────────────────────────

def chronological_split_weekly(data: pd.DataFrame) -> tuple[pd.DataFrame, pd.DataFrame]:
    data = data.sort_values("week_start").reset_index(drop=True)
    year_counts = data["week_start"].dt.year.value_counts().sort_index()
    full_years = year_counts[year_counts >= 26].index.tolist()
    if len(full_years) >= 3:
        test_year = int(full_years[-1])
        train = data[data["week_start"].dt.year < test_year].copy()
        test = data[data["week_start"].dt.year >= test_year].copy()
    else:
        split_at = int(len(data) * 0.8)
        train = data.iloc[:split_at].copy()
        test = data.iloc[split_at:].copy()
    return train, test


def prepare_weekly_data(weekly: pd.DataFrame) -> pd.DataFrame:
    weekly = weekly.copy()
    if "week_start" not in weekly.columns:
        raise ValueError("Input data must contain a 'week_start' column.")
    weekly["week_start"] = pd.to_datetime(weekly["week_start"])

    if "period" not in weekly.columns:
        iso = weekly["week_start"].dt.isocalendar()
        weekly["period"] = iso["year"].astype(str) + "-W" + iso["week"].astype(str).str.zfill(2)

    if "iso_week" not in weekly.columns:
        weekly["iso_week"] = weekly["week_start"].dt.isocalendar()["week"].astype(int)

    if "month" not in weekly.columns:
        weekly["month"] = weekly["week_start"].dt.month.astype(int)

    if "season" not in weekly.columns:
        weekly["season"] = weekly["month"].apply(season_from_month).astype(int)

    if "is_holiday_period" not in weekly.columns:
        weekly["is_holiday_period"] = weekly["week_start"].apply(is_holiday_period).astype(int)

    if "is_weekend" not in weekly.columns:
        weekly["is_weekend"] = (weekly["week_start"].dt.dayofweek >= 5).astype(int)

    if "previous_occupancy" not in weekly.columns:
        weekly["previous_occupancy"] = weekly[TARGET_COLUMN].shift(1)
        weekly["previous_occupancy"] = weekly["previous_occupancy"].fillna(weekly["previous_occupancy"].bfill())

    if "rolling_4_week_occupancy" not in weekly.columns:
        weekly["rolling_4_week_occupancy"] = weekly[TARGET_COLUMN].rolling(4, min_periods=1).mean()

    if "known_reservations_30d_before" not in weekly.columns:
        weekly["known_reservations_30d_before"] = 42

    if "known_guest_count_30d_before" not in weekly.columns:
        weekly["known_guest_count_30d_before"] = weekly["known_reservations_30d_before"] * 3.4

    if "known_average_nights_30d_before" not in weekly.columns:
        weekly["known_average_nights_30d_before"] = 4.8

    return weekly


def train_baseline_weekly(train: pd.DataFrame, test: pd.DataFrame) -> tuple[np.ndarray, dict[str, object]]:
    week_average = train.groupby("iso_week")[TARGET_COLUMN].mean().to_dict()
    month_average = train.groupby("month")[TARGET_COLUMN].mean().to_dict()
    global_average = float(train[TARGET_COLUMN].mean())
    predictions = []
    for row in test.itertuples(index=False):
        pred = week_average.get(row.iso_week)
        if pred is None:
            pred = month_average.get(row.month, global_average)
        predictions.append(float(pred))
    model = {
        "strategy": "Average occupancy by ISO week with month/global fallback",
        "week_average": {str(k): float(v) for k, v in week_average.items()},
        "month_average": {str(k): float(v) for k, v in month_average.items()},
        "global_average": global_average,
    }
    return np.asarray(predictions), model


# ── Daily data pipeline ─────────────────────────────────────────────

def chronological_split_daily(data: pd.DataFrame) -> tuple[pd.DataFrame, pd.DataFrame]:
    data = data.sort_values("stay_date").reset_index(drop=True)
    year_counts = data["stay_date"].dt.year.value_counts().sort_index()
    full_years = year_counts[year_counts >= 180].index.tolist()
    if len(full_years) >= 3:
        test_year = int(full_years[-1])
        train = data[data["stay_date"].dt.year < test_year].copy()
        test = data[data["stay_date"].dt.year >= test_year].copy()
    else:
        split_at = int(len(data) * 0.8)
        train = data.iloc[:split_at].copy()
        test = data.iloc[split_at:].copy()
    return train, test


def prepare_daily_data(daily: pd.DataFrame) -> pd.DataFrame:
    daily = daily.copy()
    if "stay_date" not in daily.columns:
        raise ValueError("Input data must contain a 'stay_date' column.")
    daily["stay_date"] = pd.to_datetime(daily["stay_date"])
    daily = daily.sort_values("stay_date").reset_index(drop=True)

    if "day_of_week" not in daily.columns:
        daily["day_of_week"] = daily["stay_date"].dt.dayofweek.astype(int)

    if "month" not in daily.columns:
        daily["month"] = daily["stay_date"].dt.month.astype(int)

    if "season" not in daily.columns:
        daily["season"] = daily["month"].apply(season_from_month).astype(int)

    if "is_holiday_period" not in daily.columns:
        daily["is_holiday_period"] = daily["stay_date"].apply(is_holiday_period).astype(int)

    if "is_weekend" not in daily.columns:
        daily["is_weekend"] = daily["day_of_week"].isin([5, 6]).astype(int)

    if "previous_occupancy" not in daily.columns:
        daily["previous_occupancy"] = daily[TARGET_COLUMN].shift(1)
        daily["previous_occupancy"] = daily["previous_occupancy"].fillna(daily[TARGET_COLUMN].iloc[0])

    if "rolling_7_day_occupancy" not in daily.columns:
        daily["rolling_7_day_occupancy"] = daily[TARGET_COLUMN].rolling(7, min_periods=1).mean()

    if "rolling_28_day_occupancy" not in daily.columns:
        daily["rolling_28_day_occupancy"] = daily[TARGET_COLUMN].rolling(28, min_periods=1).mean()

    if "known_reservations_30d_before" not in daily.columns:
        daily["known_reservations_30d_before"] = 42

    if "known_guest_count_30d_before" not in daily.columns:
        daily["known_guest_count_30d_before"] = daily["known_reservations_30d_before"] * 3.4

    if "known_average_nights_30d_before" not in daily.columns:
        daily["known_average_nights_30d_before"] = 4.8

    return daily


def train_baseline_daily(train: pd.DataFrame, test: pd.DataFrame) -> tuple[np.ndarray, dict[str, object]]:
    dow_average = train.groupby("day_of_week")[TARGET_COLUMN].mean().to_dict()
    month_average = train.groupby("month")[TARGET_COLUMN].mean().to_dict()
    global_average = float(train[TARGET_COLUMN].mean())
    predictions = []
    for row in test.itertuples(index=False):
        pred = dow_average.get(row.day_of_week)
        if pred is None:
            pred = month_average.get(row.month, global_average)
        predictions.append(float(pred))
    model = {
        "strategy": "Average occupancy by day-of-week with month/global fallback",
        "day_of_week_average": {str(k): float(v) for k, v in dow_average.items()},
        "month_average": {str(k): float(v) for k, v in month_average.items()},
        "global_average": global_average,
    }
    return np.asarray(predictions), model


# ── Train one granularity ───────────────────────────────────────────

def train_granularity(
    granularity: str,
    input_path: str,
    train_output_dir: str,
    test_output_dir: str,
    model_output_dir: str,
    report_output_dir: str,
) -> dict:
    """Train a model for the given granularity. Returns a result summary dict."""
    if granularity == "weekly":
        feature_columns = WEEKLY_FEATURE_COLUMNS
        date_col = "week_start"
        prepare_fn = prepare_weekly_data
        split_fn = chronological_split_weekly
        baseline_fn = train_baseline_weekly
        suffix = ""
        label_col = "period"
    else:
        feature_columns = DAILY_FEATURE_COLUMNS
        date_col = "stay_date"
        prepare_fn = prepare_daily_data
        split_fn = chronological_split_daily
        baseline_fn = train_baseline_daily
        suffix = "_daily"
        label_col = "stay_date"

    model_path = str(Path(model_output_dir) / f"occupancy_regressor{suffix}.pkl")
    baseline_path = str(Path(model_output_dir) / f"baseline_model{suffix}.json")
    metrics_path = str(Path(report_output_dir) / f"metrics{suffix}.json")
    predictions_path = str(Path(report_output_dir) / f"predictions{suffix}.csv")
    predictions_json_path = str(Path(report_output_dir) / f"predictions{suffix}.json")
    train_path = str(Path(train_output_dir) / f"train{suffix}.csv")
    test_path = str(Path(test_output_dir) / f"test{suffix}.csv")

    data = pd.read_csv(input_path, parse_dates=[date_col])
    data = prepare_fn(data)
    train, test = split_fn(data)

    Path(train_path).parent.mkdir(parents=True, exist_ok=True)
    Path(test_path).parent.mkdir(parents=True, exist_ok=True)
    Path(model_path).parent.mkdir(parents=True, exist_ok=True)
    Path(metrics_path).parent.mkdir(parents=True, exist_ok=True)
    train.to_csv(train_path, index=False)
    test.to_csv(test_path, index=False)

    baseline_predictions, baseline_model = baseline_fn(train, test)
    baseline_predictions = np.clip(baseline_predictions, 0, 100)
    baseline_metrics = compute_metrics(test[TARGET_COLUMN], baseline_predictions)

    candidates = train_candidates(train[feature_columns], train[TARGET_COLUMN])
    x_test = test[feature_columns]
    candidate_results: dict[str, dict[str, float]] = {}
    candidate_predictions: dict[str, np.ndarray] = {}
    for name, model in candidates.items():
        preds = np.clip(model.predict(x_test), 0, 100)
        candidate_predictions[name] = preds
        candidate_results[name] = compute_metrics(test[TARGET_COLUMN], preds)

    best_name = min(candidate_results, key=lambda n: candidate_results[n]["mae"])
    best_model = candidates[best_name]
    best_predictions = candidate_predictions[best_name]

    model_bundle = {
        "model_name": best_name,
        "model": best_model,
        "feature_columns": feature_columns,
        "target_column": TARGET_COLUMN,
        "clip_range": [0, 100],
        "granularity": granularity,
    }
    joblib.dump(model_bundle, model_path)

    with open(baseline_path, "w", encoding="utf-8") as f:
        json.dump(baseline_model, f, indent=2)

    report = {
        "granularity": granularity,
        "split": {
            "train_rows": int(len(train)),
            "test_rows": int(len(test)),
            "train_start": str(train[date_col].min().date()),
            "train_end": str(train[date_col].max().date()),
            "test_start": str(test[date_col].min().date()),
            "test_end": str(test[date_col].max().date()),
        },
        "baseline": baseline_metrics,
        "models": candidate_results,
        "best_model": best_name,
        "feature_importance": feature_importance(best_model, feature_columns),
    }
    with open(metrics_path, "w", encoding="utf-8") as f:
        json.dump(report, f, indent=2)

    # Build prediction comparison CSV
    out_cols = {date_col: test[date_col], TARGET_COLUMN: test[TARGET_COLUMN]}
    out = pd.DataFrame(out_cols).rename(columns={TARGET_COLUMN: "actual_occupancy"})
    if granularity == "weekly":
        out["period"] = test["period"]
    if granularity == "daily":
        out["day_of_week"] = out[date_col].dt.day_name()
    out["baseline_prediction"] = np.round(baseline_predictions, 2)
    out["predicted_occupancy"] = np.round(best_predictions, 2)
    out["crowd_level"] = out["predicted_occupancy"].apply(crowd_level)
    out["absolute_error"] = np.round(
        np.abs(out["actual_occupancy"] - out["predicted_occupancy"]), 2,
    )
    out.to_csv(predictions_path, index=False)

    json_out = out[[label_col, "predicted_occupancy", "crowd_level"]].copy()
    json_out.to_json(predictions_json_path, orient="records", indent=2, date_format="iso")

    return {
        "granularity": granularity,
        "train_rows": len(train),
        "test_rows": len(test),
        "baseline_mae": baseline_metrics["mae"],
        "best_model": best_name,
        "best_mae": candidate_results[best_name]["mae"],
        "model_path": model_path,
        "metrics_path": metrics_path,
    }


# ── Main ────────────────────────────────────────────────────────────

def main() -> None:
    args = parse_args()
    granularities: list[str] = []
    if args.granularity in ("weekly", "both"):
        granularities.append("weekly")
    if args.granularity in ("daily", "both"):
        granularities.append("daily")

    for gran in granularities:
        input_path = args.weekly_input if gran == "weekly" else args.daily_input
        result = train_granularity(
            granularity=gran,
            input_path=input_path,
            train_output_dir=args.train_output,
            test_output_dir=args.test_output,
            model_output_dir=args.model_output,
            report_output_dir=args.report_output,
        )
        print(
            f"[{result['granularity']}] Train: {result['train_rows']} rows | "
            f"Test: {result['test_rows']} rows | "
            f"Baseline MAE: {result['baseline_mae']} | "
            f"Best: {result['best_model']} (MAE {result['best_mae']}) | "
            f"Model → {result['model_path']}"
        )


if __name__ == "__main__":
    main()

