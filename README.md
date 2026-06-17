# Area42

Groepsproject "Area42-1" van semester 2.

## AI feature

This project includes an AI occupancy prediction pipeline under `ai/` and a
Laravel route in `software/` that trains the model and returns future
predictions.

### Required setup

1. Install PHP dependencies for the Laravel app:
   ```powershell
   cd software
   composer install
   ```
2. Create or update Laravel environment file:
   ```powershell
   cd software
   copy .env.example .env
   ```
3. Generate an application key:
   ```powershell
   php artisan key:generate
   ```
4. Configure the AI Python interpreter in `software/.env`:

   ```text
   AI_PYTHON_BINARY=C:\GithubProjects\area42\.venv\Scripts\python.exe
   ```

   If you use a different Python installation, set `AI_PYTHON_BINARY` to that
   interpreter.

5. Prepare the Python environment and install AI dependencies:
   ```powershell
   cd ..\
   python -m venv .venv
   .\.venv\Scripts\Activate.ps1
   pip install -r ai\requirements.txt
   ```

### Starting the app

Start the Laravel development server from `software/`:

```powershell
cd software
php artisan serve --host=127.0.0.1 --port=8000
```

The AI endpoint will be available at:

- `http://127.0.0.1:8000/ai/predict`

### Using the AI endpoints

| Endpoint      | Method | Purpose                                      | Time                                             |
| ------------- | ------ | -------------------------------------------- | ------------------------------------------------ |
| `/ai/train`   | POST   | Upload full CSV → preprocess → train SARIMAX | ~2 sec (weekly) / ~8 sec (daily)                 |
| `/ai/predict` | POST   | Fast forecast from pre-trained model         | ~10 ms (Python) / ~1 sec (PHP+Python round-trip) |

---

#### Input format — `POST /ai/train`

Send a JSON body with a `data` array. Each record needs exactly **2 fields**:

| Field                   | Type                | Required | Description                    |
| ----------------------- | ------------------- | -------- | ------------------------------ |
| `data`                  | array               | yes      | Historical occupancy records   |
| `data[].date`           | string (YYYY-MM-DD) | yes\*    | Date (for daily data)          |
| `data[].week_start`     | string (YYYY-MM-DD) | yes\*    | Week-start Monday (for weekly) |
| `data[].occupancy_rate` | number              | yes      | Occupancy % (0–100)            |

> \*Use either `date` (daily) or `week_start` (weekly) — auto-detected from the
> first record. Must be consistent within a request. Data is sorted by date
> server-side, order in request doesn't matter.

**Response `POST /ai/train`:**

```json
{
  "status": "trained",
  "granularity": "daily",
  "model": "Models/occupancy_daily.pkl"
}
```

---

#### Input format — `POST /ai/predict`

Send a JSON body with **3 fields** (no historical data needed — model is already
trained):

| Field         | Type                | Required | Description                       |
| ------------- | ------------------- | -------- | --------------------------------- |
| `date`        | string (YYYY-MM-DD) | yes      | First date to predict             |
| `days`        | integer             | yes      | Number of periods ahead (1–730)   |
| `granularity` | string              | no       | `"daily"` (default) or `"weekly"` |

**Response `POST /ai/predict` — 4 fields per prediction:**

```json
{
  "predictions": [
    {
      "date": "2026-06-18",
      "percentage_point": 13.83,
      "lower_bound": 7.8,
      "upper_bound": 19.86,
      "crowd_level": "laag"
    },
    {
      "date": "2026-06-19",
      "percentage_point": 14.44,
      "lower_bound": 8.03,
      "upper_bound": 20.84,
      "crowd_level": "laag"
    }
  ]
}
```

| Field              | Type   | Description                                            |
| ------------------ | ------ | ------------------------------------------------------ |
| `date`             | string | Prediction date (YYYY-MM-DD)                           |
| `percentage_point` | number | Predicted occupancy % (0–100)                          |
| `lower_bound`      | number | 80% confidence interval lower bound                    |
| `upper_bound`      | number | 80% confidence interval upper bound                    |
| `crowd_level`      | string | `"laag"` (<50%), `"normaal"` (50–80%), `"hoog"` (>80%) |

---

### Use cases

#### Use case 1 — First time: train model, then predict next week

You have a CSV of historical occupancy and no trained model yet.

```
POST /ai/train  (2–8 sec)
  → sends all historical data
  → system preprocesses (lag features, Fourier calendar terms)
  → trains SARIMAX model with baseline+residual decomposition
  → saves to Models/occupancy_daily.pkl

POST /ai/predict  (~1 sec)
  → { "date": "2026-06-18", "days": 7 }
  → returns 7 daily predictions with confidence intervals
```

#### Use case 2 — Already trained: predict without re-uploading data

Model is already trained from use case 1. Just forecast.

```
POST /ai/predict  (~1 sec)
  → { "date": "2026-07-01", "days": 14 }
  → returns 14 daily predictions instantly
```

No data upload. The model on disk is reused. Run this as often as you want.

#### Use case 3 — New data arrived: retrain, then predict

You got fresh occupancy data and want the model to learn from it.

```
POST /ai/train  (2–8 sec)
  → sends all historical data INCLUDING the new records
  → retrains from scratch, overwrites the saved model

POST /ai/predict  (~1 sec)
  → { "date": "2026-06-18", "days": 7 }
  → predictions now reflect the updated data
```

---

### What happens under the hood

The pipeline processes raw `date + occupancy_rate` into a trained model:

1. **Preprocessing** — adds shifted lag features, Fourier calendar terms, and
   booking proxies
2. **Baseline** — computes average occupancy per ISO week (with month and global
   fallback for unseen weeks)
3. **Residual modeling** — SARIMAX trains on `actual − baseline` to capture
   short-term deviations
4. **Prediction** — `forecast = baseline(date) + SARIMAX(residual|date)` clipped
   to 0–100%, with 80% confidence interval

#### Features used for training

From the raw `date` and `occupancy_rate`, the system derives these features:

| Feature | Type | Description |
|---|---|---|
| `iso_week` | integer | ISO week number (1–53) |
| `month` | integer | Month (1–12) |
| `day_of_week` | integer | Day of week (0=Mon, 6=Sun) — daily only |
| `is_weekend` | binary | 1 if Saturday or Sunday |
| `is_holiday_period` | binary | 1 during school holidays (summer, May, autumn, Christmas) |
| `previous_occupancy` | float | `occupancy_rate` from the **previous** day/week (`.shift(1)` — no data leakage) |
| `rolling_*_occupancy` | float | Rolling mean of past occupancy (4-week for weekly; 7-day & 28-day for daily) |
| `known_reservations_30d_before` | integer | Reservations known 30 days ahead (42, a calibrated proxy) |
| `known_guest_count_30d_before` | float | `known_reservations × 3.4` (estimated guests) |
| `known_average_nights_30d_before` | float | Average stay length (4.8 nights, a proxy) |
| `day/week/month_sin` | float | Sine transform of cyclic time (Fourier term, period=365.25/52/12) |
| `day/week/month_cos` | float | Cosine transform of cyclic time |

> **Why Fourier terms?** Month "12" and month "1" are adjacent in a year but not
> numerically. Sine/cosine encoding (`sin(2π × month/12)`) captures this
> circular relationship, letting the model learn that December and January
> behave similarly.

#### Model performance

| Metric | Weekly | Daily |
|---|---|---|
| Model | SARIMAX(1,1,1) | SARIMAX(2,0,0) |
| MAE | 2.51% | 4.08% |
| RMSE | 3.47% | 5.69% |
| Train rows | 106 | 731 |
| Test rows | 54 | 378 |
| Data range | 2022‑W52 – 2026‑W03 | 2023‑01‑01 – 2026‑01‑13 |

The weekly model is more accurate (averaging out daily noise). Both models
correctly capture the seasonal pattern: winter lows (~2%), spring ramp (~25%),
summer peak (~73%), autumn decline (~10%).

### Direct Python workflow

```powershell
cd ai

# Train both models (run once, or on a schedule)
python scripts/train_model.py --granularity both

# Predict using pre-trained model (fast)
python scripts/predict_occupancy.py --granularity daily --date 2026-06-17 --days 30

# Generate forecast graph
python scripts/plot_forecast.py
```
