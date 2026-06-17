"""Predict occupancy using a pre-trained SARIMAX model.

Loads the saved model + metadata, generates future dates from a given
start date, and outputs predictions. No training — pure inference.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path

import numpy as np
import pandas as pd
from statsmodels.tsa.statespace.sarimax import SARIMAXResults

PROJECT_ROOT = Path(__file__).resolve().parent.parent


def parse_args() -> argparse.Namespace:
    p = argparse.ArgumentParser(description="Predict occupancy from pre-trained model.")
    p.add_argument("--granularity", choices=["daily", "weekly"], default="daily")
    p.add_argument("--date", required=True, help="Start date for predictions (YYYY-MM-DD)")
    p.add_argument("--days", type=int, required=True, help="Periods to predict ahead")
    return p.parse_args()


def is_holiday(day: pd.Timestamp) -> int:
    m, w = int(day.month), int(day.isocalendar().week)
    return int(m in (7, 8) or w in range(17, 20) or w in range(30, 36)
               or w in range(42, 44) or (m == 12 and day.day >= 20) or (m == 1 and day.day <= 5))


def crowd_level(v: float) -> str:
    return "laag" if v < 50 else "normaal" if v < 80 else "hoog"


def build_future_daily(start: pd.Timestamp, days: int) -> pd.DataFrame:
    dates = pd.date_range(start=start, periods=days, freq="D")
    rows = [{"stay_date": d, "iso_week": int(d.isocalendar().week),
             "month": int(d.month), "is_holiday_period": is_holiday(d)} for d in dates]
    df = pd.DataFrame(rows)
    wa = 2 * np.pi * df["iso_week"] / 52.0
    ma = 2 * np.pi * df["month"] / 12.0
    da = 2 * np.pi * df["iso_week"] / 365.25
    df["day_sin_1"], df["day_cos_1"] = np.sin(da), np.cos(da)
    df["day_sin_2"], df["day_cos_2"] = np.sin(2 * da), np.cos(2 * da)
    df["week_sin_1"], df["week_cos_1"] = np.sin(wa), np.cos(wa)
    df["week_sin_2"], df["week_cos_2"] = np.sin(2 * wa), np.cos(2 * wa)
    df["month_sin"], df["month_cos"] = np.sin(ma), np.cos(ma)
    return df


def build_future_weekly(start: pd.Timestamp, weeks: int) -> pd.DataFrame:
    dates = pd.date_range(start=start, periods=weeks, freq="W-MON")
    rows = [{"week_start": d, "iso_week": int(d.isocalendar().week),
             "month": int(d.month), "is_holiday_period": is_holiday(d)} for d in dates]
    df = pd.DataFrame(rows)
    wa = 2 * np.pi * df["iso_week"] / 52.0
    ma = 2 * np.pi * df["month"] / 12.0
    df["week_sin_1"], df["week_cos_1"] = np.sin(wa), np.cos(wa)
    df["week_sin_2"], df["week_cos_2"] = np.sin(2 * wa), np.cos(2 * wa)
    df["month_sin"], df["month_cos"] = np.sin(ma), np.cos(ma)
    return df


def main() -> None:
    args = parse_args()
    is_weekly = args.granularity == "weekly"
    suffix = "weekly" if is_weekly else "daily"

    model_path = PROJECT_ROOT / "Models" / f"occupancy_{suffix}.pkl"
    meta_path = PROJECT_ROOT / "Models" / f"occupancy_{suffix}_metadata.json"
    output_path = PROJECT_ROOT / "Reports" / f"future_predictions_{suffix}.csv"

    model = SARIMAXResults.load(str(model_path))
    with open(meta_path) as f:
        meta = json.load(f)

    bl = meta["baseline_model"]
    week_avg = {int(k): v for k, v in bl["week_average"].items()}
    month_avg = {int(k): v for k, v in bl["month_average"].items()}
    global_avg = float(bl["global_average"])
    exog_cols = meta["exog_columns"]

    start_date = pd.Timestamp(args.date)
    if is_weekly:
        future = build_future_weekly(start_date, args.days)
    else:
        future = build_future_daily(start_date, args.days)

    baseline = np.array([
        week_avg.get(r.iso_week, month_avg.get(r.month, global_avg))
        for r in future.itertuples(index=False)
    ])
    baseline = np.clip(baseline, 0, 100)

    fc = model.get_forecast(steps=args.days, exog=future[exog_cols])
    preds = np.clip(baseline + fc.predicted_mean.to_numpy(), 0, 100)
    ci = fc.conf_int(alpha=0.2)
    lo = np.clip(baseline + ci.iloc[:, 0].to_numpy(), 0, 100)
    hi = np.clip(baseline + ci.iloc[:, 1].to_numpy(), 0, 100)

    date_col = "week_start" if is_weekly else "stay_date"
    out = future[[date_col]].copy()
    out["predicted_occupancy"] = np.round(preds, 2)
    out["lower_bound"] = np.round(lo, 2)
    out["upper_bound"] = np.round(hi, 2)
    out["crowd_level"] = [crowd_level(v) for v in preds]

    output_path.parent.mkdir(parents=True, exist_ok=True)
    out.to_csv(output_path, index=False)
    print(f"[{args.granularity}] Wrote {len(out)} predictions to {output_path}")


if __name__ == "__main__":
    main()
