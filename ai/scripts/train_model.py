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
