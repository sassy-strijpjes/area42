"""Test script: speed, accuracy, and visualization for the occupancy predictor."""
from __future__ import annotations

import time
import json
import subprocess
from pathlib import Path

import joblib
import matplotlib
matplotlib.use("Agg")  # non-interactive backend
import matplotlib.pyplot as plt
import matplotlib.dates as mdates
import numpy as np
import pandas as pd
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score

PROJECT_ROOT = Path(__file__).resolve().parent.parent

# ── Import prepare helpers from train_model ─────────────────────────
import sys
sys.path.insert(0, str(PROJECT_ROOT / "scripts"))
from train_model import (
    prepare_daily_data, prepare_weekly_data,
    chronological_split_daily, chronological_split_weekly,
    DAILY_FEATURE_COLUMNS, WEEKLY_FEATURE_COLUMNS, TARGET_COLUMN,
)

# ── Load raw data ───────────────────────────────────────────────────

daily_raw = pd.read_csv(
    PROJECT_ROOT / "Data/Processed/occupancy_daily.csv",
    parse_dates=["stay_date"],
).sort_values("stay_date")

weekly_raw = pd.read_csv(
    PROJECT_ROOT / "Data/Processed/occupancy_weekly.csv",
    parse_dates=["week_start"],
).sort_values("week_start")

# ── Prepare features ────────────────────────────────────────────────

daily_data = prepare_daily_data(daily_raw)
weekly_data = prepare_weekly_data(weekly_raw)

# ── Load models ─────────────────────────────────────────────────────

daily_bundle = joblib.load(PROJECT_ROOT / "Models/occupancy_regressor_daily.pkl")
weekly_bundle = joblib.load(PROJECT_ROOT / "Models/occupancy_regressor.pkl")
daily_model = daily_bundle["model"]
weekly_model = weekly_bundle["model"]

# ── Train-test split ────────────────────────────────────────────────

daily_train, daily_test = chronological_split_daily(daily_data)
weekly_train, weekly_test = chronological_split_weekly(weekly_data)

# ── Speed test ──────────────────────────────────────────────────────

print("=" * 70)
print("SPEED TEST")
print("=" * 70)

_, daily_test = chronological_split_daily(daily_data)
_, weekly_test = chronological_split_weekly(weekly_data)

# Daily prediction speed (predict all test rows)
x_daily_test = daily_test[DAILY_FEATURE_COLUMNS]
start = time.perf_counter()
daily_predictions = np.clip(daily_model.predict(x_daily_test), 0, 100)
daily_time = (time.perf_counter() - start) * 1000  # ms
daily_per_row = daily_time / len(daily_test)

print(f"Daily model: {len(daily_test)} rows predicted in {daily_time:.1f} ms ({daily_per_row:.2f} ms/row)")

# Weekly prediction speed
x_weekly_test = weekly_test[WEEKLY_FEATURE_COLUMNS]
start = time.perf_counter()
weekly_predictions = np.clip(weekly_model.predict(x_weekly_test), 0, 100)
weekly_time = (time.perf_counter() - start) * 1000
weekly_per_row = weekly_time / len(weekly_test)

print(f"Weekly model: {len(weekly_test)} rows predicted in {weekly_time:.1f} ms ({weekly_per_row:.2f} ms/row)")

# Full pipeline timing: predict 120 days from CLI
start = time.perf_counter()
result = subprocess.run(
    [PROJECT_ROOT.parent / ".venv/bin/python", "scripts/predict_occupancy.py",
     "--granularity", "daily", "--periods", "120"],
    cwd=str(PROJECT_ROOT),
    capture_output=True, text=True,
)
pipeline_time = (time.perf_counter() - start) * 1000
print(f"Full pipeline (train → predict 90 days): {pipeline_time:.0f} ms")
if result.stdout:
    print(result.stdout.strip())

# ── Accuracy test ───────────────────────────────────────────────────

print()
print("=" * 70)
print("ACCURACY TEST")
print("=" * 70)

from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score

# Daily accuracy
daily_mae = mean_absolute_error(daily_test["occupancy_rate"], daily_predictions)
daily_rmse = np.sqrt(mean_squared_error(daily_test["occupancy_rate"], daily_predictions))
daily_r2 = r2_score(daily_test["occupancy_rate"], daily_predictions)

# SMAPE
daily_denom = (np.abs(daily_test["occupancy_rate"]) + np.abs(daily_predictions)) / 2
daily_smape = np.mean(
    np.where(daily_denom == 0, 0, np.abs(daily_test["occupancy_rate"] - daily_predictions) / daily_denom)
) * 100

print(f"Daily  — MAE: {daily_mae:.2f}%, RMSE: {daily_rmse:.2f}%, SMAPE: {daily_smape:.2f}%, R²: {daily_r2:.4f}")

# Weekly accuracy
weekly_mae = mean_absolute_error(weekly_test["occupancy_rate"], weekly_predictions)
weekly_rmse = np.sqrt(mean_squared_error(weekly_test["occupancy_rate"], weekly_predictions))
weekly_r2 = r2_score(weekly_test["occupancy_rate"], weekly_predictions)

weekly_denom = (np.abs(weekly_test["occupancy_rate"]) + np.abs(weekly_predictions)) / 2
weekly_smape = np.mean(
    np.where(weekly_denom == 0, 0, np.abs(weekly_test["occupancy_rate"] - weekly_predictions) / weekly_denom)
) * 100

print(f"Weekly — MAE: {weekly_mae:.2f}%, RMSE: {weekly_rmse:.2f}%, SMAPE: {weekly_smape:.2f}%, R²: {weekly_r2:.4f}")

# Per-season daily breakdown
print()
print("Daily accuracy by season:")
seasons = {0: "Winter", 1: "Spring", 2: "Summer", 3: "Autumn"}
for season_id, season_name in seasons.items():
    mask = daily_test["season"] == season_id
    if mask.sum() > 0:
        mae_s = mean_absolute_error(
            daily_test.loc[mask, "occupancy_rate"],
            daily_predictions[mask.to_numpy()],
        )
        print(f"  {season_name:>7}: MAE {mae_s:.2f}% (n={mask.sum()})")

# ── Graph ───────────────────────────────────────────────────────────

print()
print("=" * 70)
print("GENERATING GRAPH")
print("=" * 70)

# Use the last 60 days of real data, then append 90 predicted days
last_real_days = 90  # show last 90 real days for context
real_subset = daily_data.tail(last_real_days).copy()

# Load the 90-day future predictions
future_csv = PROJECT_ROOT / "Reports/future_predictions_daily.csv"
future_data = pd.read_csv(future_csv, parse_dates=["stay_date"])

fig, ax = plt.subplots(figsize=(16, 7))

# Real data
ax.plot(real_subset["stay_date"], real_subset["occupancy_rate"],
        color="#2c7fb8", linewidth=2, label="Historical (actual)", zorder=3)

# Predicted data
ax.plot(future_data["stay_date"], future_data["predicted_occupancy"],
        color="#e6550d", linewidth=2, linestyle="--", label="Predicted (daily model)", zorder=4)

# Shade the prediction zone
ax.axvspan(future_data["stay_date"].iloc[0], future_data["stay_date"].iloc[-1],
           alpha=0.08, color="orange", label="Forecast zone")

# Crowd level bands
ax.axhspan(0, 50, alpha=0.06, color="green")
ax.axhspan(50, 80, alpha=0.06, color="yellow")
ax.axhspan(80, 100, alpha=0.06, color="red")
ax.text(future_data["stay_date"].iloc[-1], 25, "Laag", fontsize=9, color="green", ha="right", alpha=0.6)
ax.text(future_data["stay_date"].iloc[-1], 65, "Normaal", fontsize=9, color="orange", ha="right", alpha=0.6)
ax.text(future_data["stay_date"].iloc[-1], 90, "Hoog", fontsize=9, color="red", ha="right", alpha=0.6)

# Formatting
ax.set_xlabel("Date")
ax.set_ylabel("Occupancy (%)")
ax.set_title(f"Area42 Occupancy — Real (last {last_real_days} days) + 90-day Forecast\n"
             f"Daily MAE: {daily_mae:.1f}% | Weekly MAE: {weekly_mae:.1f}%",
             fontsize=13, fontweight="bold")
ax.xaxis.set_major_formatter(mdates.DateFormatter("%b %d"))
ax.xaxis.set_major_locator(mdates.WeekdayLocator(interval=2))
fig.autofmt_xdate(rotation=45)
ax.set_ylim(0, 105)
ax.legend(loc="upper left")
ax.grid(True, alpha=0.3)

output_path = PROJECT_ROOT / "Reports" / "occupancy_forecast.png"
output_path.parent.mkdir(parents=True, exist_ok=True)
fig.tight_layout()
fig.savefig(output_path, dpi=150)
print(f"Graph saved to: {output_path}")

# ── Summary JSON ────────────────────────────────────────────────────

summary = {
    "speed": {
        "daily_predict_ms": round(daily_time, 1),
        "daily_per_row_ms": round(daily_per_row, 2),
        "weekly_predict_ms": round(weekly_time, 1),
        "weekly_per_row_ms": round(weekly_per_row, 2),
        "full_pipeline_90_days_ms": round(pipeline_time, 0),
    },
    "accuracy": {
        "daily": {"mae_pct": round(daily_mae, 2), "rmse_pct": round(daily_rmse, 2),
                  "smape_pct": round(daily_smape, 2), "r2": round(daily_r2, 4)},
        "weekly": {"mae_pct": round(weekly_mae, 2), "rmse_pct": round(weekly_rmse, 2),
                   "smape_pct": round(weekly_smape, 2), "r2": round(weekly_r2, 4)},
    },
}

summary_path = PROJECT_ROOT / "Reports" / "test_summary.json"
with open(summary_path, "w") as f:
    json.dump(summary, f, indent=2)
print(f"Summary saved to: {summary_path}")
