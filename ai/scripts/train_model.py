from __future__ import annotations

import argparse
import json
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from sklearn.ensemble import GradientBoostingRegressor, RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error


FEATURE_COLUMNS = [
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
TARGET_COLUMN = "occupancy_rate"


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Train first Area-42 occupancy prediction model.")
    parser.add_argument("--input", default="Data/Processed/occupancy_weekly.csv")
    parser.add_argument("--train-output", default="Data/Training/train_weekly.csv")
    parser.add_argument("--test-output", default="Data/Test/test_weekly.csv")
    parser.add_argument("--model-output", default="Models/occupancy_regressor.pkl")
    parser.add_argument("--baseline-output", default="Models/baseline_model.json")
    parser.add_argument("--metrics-output", default="Reports/metrics.json")
    parser.add_argument("--predictions-output", default="Reports/predictions.csv")
    parser.add_argument("--predictions-json-output", default="Reports/predictions.json")
    return parser.parse_args()


def chronological_split(data: pd.DataFrame) -> tuple[pd.DataFrame, pd.DataFrame]:
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


def smape(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    denominator = (np.abs(y_true) + np.abs(y_pred)) / 2
    values = np.where(denominator == 0, 0, np.abs(y_true - y_pred) / denominator)
    return float(np.mean(values) * 100)


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


def prepare_weekly_data(weekly: pd.DataFrame) -> pd.DataFrame:
    weekly = weekly.copy()

    if "week_start" not in weekly.columns:
        raise ValueError("Input data must contain a 'week_start' column.")

    weekly["week_start"] = pd.to_datetime(weekly["week_start"])

    if "period" not in weekly.columns:
        iso = weekly["week_start"].dt.isocalendar()
        year = iso["year"].astype(str)
        week = iso["week"].astype(str).str.zfill(2)
        weekly["period"] = year + "-W" + week

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


def metrics(y_true: pd.Series, y_pred: np.ndarray) -> dict[str, float]:
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


def train_baseline(train: pd.DataFrame, test: pd.DataFrame) -> tuple[np.ndarray, dict[str, object]]:
    week_average = train.groupby("iso_week")[TARGET_COLUMN].mean().to_dict()
    month_average = train.groupby("month")[TARGET_COLUMN].mean().to_dict()
    global_average = float(train[TARGET_COLUMN].mean())

    predictions = []
    for row in test.itertuples(index=False):
        prediction = week_average.get(row.iso_week)
        if prediction is None:
            prediction = month_average.get(row.month, global_average)
        predictions.append(float(prediction))

    model = {
        "strategy": "Average occupancy by ISO week with month/global fallback",
        "week_average": {str(key): float(value) for key, value in week_average.items()},
        "month_average": {str(key): float(value) for key, value in month_average.items()},
        "global_average": global_average,
    }
    return np.asarray(predictions), model


def train_candidates(train: pd.DataFrame) -> dict[str, object]:
    x_train = train[FEATURE_COLUMNS]
    y_train = train[TARGET_COLUMN]
    models = {
        "random_forest": RandomForestRegressor(
            n_estimators=250,
            max_depth=8,
            min_samples_leaf=2,
            random_state=42,
        ),
        "gradient_boosting": GradientBoostingRegressor(
            n_estimators=180,
            learning_rate=0.045,
            max_depth=3,
            random_state=42,
        ),
    }
    for model in models.values():
        model.fit(x_train, y_train)
    return models


def feature_importance(model: object) -> list[dict[str, float]]:
    importances = getattr(model, "feature_importances_", None)
    if importances is None:
        return []
    rows = [
        {"feature": feature, "importance": round(float(importance), 6)}
        for feature, importance in zip(FEATURE_COLUMNS, importances)
    ]
    return sorted(rows, key=lambda row: row["importance"], reverse=True)


def main() -> None:
    args = parse_args()
    weekly = pd.read_csv(args.input, parse_dates=["week_start"])
    weekly = prepare_weekly_data(weekly)
    train, test = chronological_split(weekly)

    Path(args.train_output).parent.mkdir(parents=True, exist_ok=True)
    Path(args.test_output).parent.mkdir(parents=True, exist_ok=True)
    Path(args.model_output).parent.mkdir(parents=True, exist_ok=True)
    Path(args.metrics_output).parent.mkdir(parents=True, exist_ok=True)
    train.to_csv(args.train_output, index=False)
    test.to_csv(args.test_output, index=False)

    baseline_predictions, baseline_model = train_baseline(train, test)
    baseline_predictions = np.clip(baseline_predictions, 0, 100)
    baseline_metrics = metrics(test[TARGET_COLUMN], baseline_predictions)

    candidates = train_candidates(train)
    x_test = test[FEATURE_COLUMNS]
    candidate_results = {}
    candidate_predictions = {}
    for name, model in candidates.items():
        predictions = np.clip(model.predict(x_test), 0, 100)
        candidate_predictions[name] = predictions
        candidate_results[name] = metrics(test[TARGET_COLUMN], predictions)

    best_name = min(candidate_results, key=lambda name: candidate_results[name]["mae"])
    best_model = candidates[best_name]
    best_predictions = candidate_predictions[best_name]

    model_bundle = {
        "model_name": best_name,
        "model": best_model,
        "feature_columns": FEATURE_COLUMNS,
        "target_column": TARGET_COLUMN,
        "clip_range": [0, 100],
    }
    joblib.dump(model_bundle, args.model_output)

    with open(args.baseline_output, "w", encoding="utf-8") as file:
        json.dump(baseline_model, file, indent=2)

    report = {
        "split": {
            "train_rows": int(len(train)),
            "test_rows": int(len(test)),
            "train_start": str(train["week_start"].min().date()),
            "train_end": str(train["week_start"].max().date()),
            "test_start": str(test["week_start"].min().date()),
            "test_end": str(test["week_start"].max().date()),
        },
        "baseline": baseline_metrics,
        "models": candidate_results,
        "best_model": best_name,
        "feature_importance": feature_importance(best_model),
    }
    with open(args.metrics_output, "w", encoding="utf-8") as file:
        json.dump(report, file, indent=2)

    output = test[["period", "week_start", TARGET_COLUMN]].copy()
    output = output.rename(columns={TARGET_COLUMN: "actual_occupancy"})
    output["baseline_prediction"] = np.round(baseline_predictions, 2)
    output["predicted_occupancy"] = np.round(best_predictions, 2)
    output["crowd_level"] = output["predicted_occupancy"].apply(crowd_level)
    output["absolute_error"] = np.round(
        np.abs(output["actual_occupancy"] - output["predicted_occupancy"]),
        2,
    )
    output.to_csv(args.predictions_output, index=False)
    output[["period", "predicted_occupancy", "crowd_level"]].to_json(
        args.predictions_json_output,
        orient="records",
        indent=2,
    )

    print(f"Train rows: {len(train)}")
    print(f"Test rows: {len(test)}")
    print(f"Baseline MAE: {baseline_metrics['mae']}")
    print(f"Best model: {best_name}")
    print(f"Best model MAE: {candidate_results[best_name]['mae']}")
    print(f"Wrote model to {args.model_output}")
    print(f"Wrote metrics to {args.metrics_output}")


if __name__ == "__main__":
    main()
