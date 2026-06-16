from __future__ import annotations

import argparse
from pathlib import Path

import joblib
import numpy as np
import pandas as pd

# Resolve paths relative to the ai/ folder (parent of the scripts/ folder)
PROJECT_ROOT = Path(__file__).resolve().parent.parent


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Create future Area-42 occupancy predictions.")
    parser.add_argument(
        "--granularity",
        choices=["weekly", "daily"],
        default="weekly",
        help="Prediction granularity: weekly or daily (default: weekly).",
    )
    parser.add_argument("--model", default=None, help="Path to model .pkl (auto-detected from --granularity)")
    parser.add_argument("--history", default=None, help="Path to history CSV (auto-detected from --granularity)")
    parser.add_argument("--periods", type=int, default=None, help="Number of weeks/days to predict (default: 52 weeks, 90 days)")
    parser.add_argument("--output", default=None, help="Path to output CSV (auto-detected)")
    return parser.parse_args()


# ── Shared helpers ──────────────────────────────────────────────────

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


def day_over_day_change(today: float, yesterday: float) -> str:
    """Return a human-readable day-over-day change description."""
    diff = today - yesterday
    if diff > 5:
        return f"↑ sterkere bezetting (+{diff:.1f}%)"
    if diff > 1:
        return f"↑ lichte stijging (+{diff:.1f}%)"
    if diff < -5:
        return f"↓ sterkere daling ({diff:.1f}%)"
    if diff < -1:
        return f"↓ lichte daling ({diff:.1f}%)"
    return "→ stabiel"


# ── Weekly prediction ───────────────────────────────────────────────

def predict_weekly(args: argparse.Namespace) -> None:
    model_path = args.model or str(PROJECT_ROOT / "Models/occupancy_regressor.pkl")
    history_path = args.history or str(PROJECT_ROOT / "Data/Processed/occupancy_weekly.csv")
    weeks = args.periods if args.periods is not None else 52
    output_path = args.output or str(PROJECT_ROOT / "Reports/future_predictions.csv")

    bundle = joblib.load(model_path)
    history = pd.read_csv(history_path, parse_dates=["week_start"]).sort_values("week_start")
    feature_columns = bundle["feature_columns"]

    rolling_history = list(history["occupancy_rate"].tail(4))
    previous_occupancy = float(history["occupancy_rate"].iloc[-1])
    next_week_start = history["week_start"].iloc[-1] + pd.Timedelta(days=7)
    rows = []

    for offset in range(weeks):
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

    output = Path(output_path)
    output.parent.mkdir(parents=True, exist_ok=True)
    pd.DataFrame(rows).to_csv(output, index=False)
    print(f"[weekly] Wrote {len(rows)} future predictions to {output}")


# ── Daily prediction ────────────────────────────────────────────────

def predict_daily(args: argparse.Namespace) -> None:
    model_path = args.model or str(PROJECT_ROOT / "Models/occupancy_regressor_daily.pkl")
    history_path = args.history or str(PROJECT_ROOT / "Data/Processed/occupancy_daily.csv")
    days = args.periods if args.periods is not None else 90
    output_path = args.output or str(PROJECT_ROOT / "Reports/future_predictions_daily.csv")

    bundle = joblib.load(model_path)
    history = pd.read_csv(history_path, parse_dates=["stay_date"]).sort_values("stay_date")
    feature_columns = bundle["feature_columns"]

    occupancy_history = list(history["occupancy_rate"].tail(28))
    previous_occupancy = float(history["occupancy_rate"].iloc[-1])
    next_date = history["stay_date"].iloc[-1] + pd.Timedelta(days=1)
    rows = []

    for offset in range(days):
        pred_date = next_date + pd.Timedelta(days=offset)
        month = int(pred_date.month)
        day_of_week = int(pred_date.dayofweek)
        known_reservations = max(0, int(np.random.default_rng(42 + offset).normal(6, 3)))
        known_guests = known_reservations * 3.4
        known_average_nights = 4.8

        row = {
            "day_of_week": day_of_week,
            "month": month,
            "season": season_from_month(month),
            "is_holiday_period": is_holiday_period(pred_date),
            "is_weekend": 1 if day_of_week >= 5 else 0,
            "previous_occupancy": previous_occupancy,
            "rolling_7_day_occupancy": float(np.mean(occupancy_history[-7:])),
            "rolling_28_day_occupancy": float(np.mean(occupancy_history[-28:])),
            "known_reservations_30d_before": known_reservations,
            "known_guest_count_30d_before": known_guests,
            "known_average_nights_30d_before": known_average_nights,
        }

        prediction = float(bundle["model"].predict(pd.DataFrame([row])[feature_columns])[0])
        prediction = min(max(prediction, 0), 100)
        yesterday_value = previous_occupancy

        rows.append(
            {
                "stay_date": pred_date.date().isoformat(),
                "day_of_week": pred_date.day_name(),
                "predicted_occupancy": round(prediction, 2),
                "yesterday_occupancy": round(yesterday_value, 2),
                "day_over_day_change": day_over_day_change(prediction, yesterday_value),
                "crowd_level": crowd_level(prediction),
            }
        )

        previous_occupancy = prediction
        occupancy_history.append(prediction)

    output = Path(output_path)
    output.parent.mkdir(parents=True, exist_ok=True)
    pd.DataFrame(rows).to_csv(output, index=False)
    print(f"[daily] Wrote {len(rows)} future predictions to {output}")
    print("Sample predictions:")
    for r in rows[:5]:
        print(f"  {r['stay_date']} ({r['day_of_week']}): {r['predicted_occupancy']}% — {r['day_over_day_change']}")


# ── Main ────────────────────────────────────────────────────────────

def main() -> None:
    args = parse_args()
    if args.granularity == "daily":
        predict_daily(args)
    else:
        predict_weekly(args)


if __name__ == "__main__":
    main()

