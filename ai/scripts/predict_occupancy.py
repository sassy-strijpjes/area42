from __future__ import annotations

import argparse
from pathlib import Path

import joblib
import numpy as np
import pandas as pd


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Create future Area-42 occupancy predictions.")
    parser.add_argument("--model", default="Models/occupancy_regressor.pkl")
    parser.add_argument("--history", default="Data/Processed/occupancy_weekly.csv")
    parser.add_argument("--weeks", type=int, default=52)
    parser.add_argument("--output", default="Reports/future_predictions.csv")
    return parser.parse_args()


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


def crowd_level(value: float) -> str:
    if value < 50:
        return "laag"
    if value < 80:
        return "normaal"
    return "hoog"


def main() -> None:
    args = parse_args()
    bundle = joblib.load(args.model)
    history = pd.read_csv(args.history, parse_dates=["week_start"]).sort_values("week_start")
    feature_columns = bundle["feature_columns"]

    rolling_history = list(history["occupancy_rate"].tail(4))
    previous_occupancy = float(history["occupancy_rate"].iloc[-1])
    next_week_start = history["week_start"].iloc[-1] + pd.Timedelta(days=7)
    rows = []

    for offset in range(args.weeks):
        week_start = next_week_start + pd.Timedelta(days=offset * 7)
        iso = week_start.isocalendar()
        month = int(week_start.month)
        known_reservations = max(0, int(np.random.default_rng(42 + offset).normal(42, 18)))
        known_guests = known_reservations * 3.4
        known_average_nights = 4.8

        row = {
            "iso_week": int(iso.week),
            "month": month,
            "season": season_from_month(month),
            "is_holiday_period": is_holiday_period(week_start),
            "is_weekend": 1,
            "previous_occupancy": previous_occupancy,
            "rolling_4_week_occupancy": float(np.mean(rolling_history[-4:])),
            "known_reservations_30d_before": known_reservations,
            "known_guest_count_30d_before": known_guests,
            "known_average_nights_30d_before": known_average_nights,
        }
        prediction = float(bundle["model"].predict(pd.DataFrame([row])[feature_columns])[0])
        prediction = min(max(prediction, 0), 100)
        rows.append(
            {
                "period": f"{int(iso.year)}-W{int(iso.week):02d}",
                "week_start": week_start.date().isoformat(),
                "predicted_occupancy": round(prediction, 2),
                "crowd_level": crowd_level(prediction),
            }
        )
        previous_occupancy = prediction
        rolling_history.append(prediction)

    output = Path(args.output)
    output.parent.mkdir(parents=True, exist_ok=True)
    pd.DataFrame(rows).to_csv(output, index=False)
    print(f"Wrote {len(rows)} future predictions to {output}")


if __name__ == "__main__":
    main()
