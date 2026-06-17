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

The AI feature has two endpoints:

| Endpoint      | Method | Purpose                                    | Speed    |
| ------------- | ------ | ------------------------------------------ | -------- |
| `/ai/train`   | POST   | Upload full CSV → preprocess → train model | ~2-8 sec |
| `/ai/predict` | POST   | Fast forecast from pre-trained model       | ~10 ms   |

---

#### `POST /ai/train` — Train the model

Upload your full historical occupancy dataset. The system preprocesses it (adds
lag features, Fourier calendar terms) and trains a SARIMAX model.

**Request body:**

| Field                   | Type                | Required | Description                               |
| ----------------------- | ------------------- | -------- | ----------------------------------------- |
| `data`                  | array               | yes      | Full historical occupancy records (min 1) |
| `data[].date`           | string (YYYY-MM-DD) | yes\*    | Date of the record                        |
| `data[].week_start`     | string (YYYY-MM-DD) | yes\*    | Week-start date (use for weekly data)     |
| `data[].occupancy_rate` | number              | yes      | Occupancy percentage (0–100)              |

> \*Use `date` for daily data, `week_start` for weekly. Auto-detected.

**Response:**

```json
{
  "status": "trained",
  "granularity": "daily",
  "model": "Models/occupancy_sarimax_daily.pkl"
}
```

#### `POST /ai/predict` — Get predictions

Fast forecast using the pre-trained model. No training data needed — just send
the start date and number of days.

**Request body:**

| Field         | Type                | Required | Description                       |
| ------------- | ------------------- | -------- | --------------------------------- |
| `date`        | string (YYYY-MM-DD) | yes      | First prediction date             |
| `days`        | integer             | yes      | Number of periods ahead (1–730)   |
| `granularity` | string              | no       | `"daily"` (default) or `"weekly"` |

**Response:**

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

#### Example — Train then predict (curl)

```powershell
curl.exe -i -c csrf_cookies.txt http://127.0.0.1:8000/
$token = [uri]::UnescapeDataString((Get-Content csrf_cookies.txt | Where-Object { $_ -match 'XSRF-TOKEN' } | ForEach-Object { ($_ -split '\t')[6] }))

# Step 1: Train (upload full CSV data)
$trainBody = '{"data":[{"date":"2023-01-01","occupancy_rate":2.5},{"date":"2023-01-02","occupancy_rate":5.0},...]}'
Set-Content -Path ai_train.json -Value $trainBody
curl.exe -X POST http://127.0.0.1:8000/ai/train -H "Accept: application/json" -H "Content-Type: application/json" -H "X-XSRF-TOKEN: $token" --cookie csrf_cookies.txt --data-binary '@ai_train.json'

# Step 2: Predict (no data needed — uses pre-trained model)
$predictBody = '{"date":"2026-06-17","days":7,"granularity":"daily"}'
Set-Content -Path ai_predict.json -Value $predictBody
curl.exe -X POST http://127.0.0.1:8000/ai/predict -H "Accept: application/json" -H "Content-Type: application/json" -H "X-XSRF-TOKEN: $token" --cookie csrf_cookies.txt --data-binary '@ai_predict.json'
```

### Direct Python workflow

```powershell
cd ai
# Preprocess data
python scripts/preprocess_daily.py
python scripts/preprocess_weekly.py

# Train SARIMAX models (run offline/scheduled)
python scripts/train_sarimax_model.py --granularity both

# Predict using pre-trained models (fast, ~10 ms)
python scripts/predict_sarimax.py --granularity daily --date 2026-06-17 --days 30

# Generate visualization graph
python scripts/plot_occupancy_forecast.py --granularity daily
```

### Model architecture

The SARIMAX model uses a baseline + residual decomposition approach:

1. **Baseline**: ISO-week average occupancy (with month/global fallback)
2. **Residual**: SARIMAX model on the difference between actual and baseline
3. **Prediction**: `baseline + SARIMAX(residual)` with 80% confidence interval
4. **Calendar features**: Sine/cosine Fourier terms for week and month cycles

The model is pre-trained offline. The predict endpoint only loads the saved
model and generates the forecast — no retraining per request.
