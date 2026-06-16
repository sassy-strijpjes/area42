# Area42

Groepsproject "Area42-1" van semester 2.

## AI feature

This project includes an AI occupancy prediction pipeline under `ai/` and a Laravel route in `software/` that trains the model and returns future predictions.

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
   If you use a different Python installation, set `AI_PYTHON_BINARY` to that interpreter.

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

### Using the AI endpoint

**Endpoint:** `POST http://127.0.0.1:8000/ai/predict`

#### Request shape

| Field | Type | Required | Description |
|---|---|---|---|
| `data` | array | yes | Array of historical occupancy records (min 1) |
| `data[].week_start` | string (YYYY-MM-DD) | yes* | Start date of the week |
| `data[].date` | string (YYYY-MM-DD) | yes* | Date (alternative to `week_start`; daily granularity) |
| `data[].occupancy_rate` | number | yes | Occupancy percentage (0–100) |
| `days` | integer | yes | Number of days to predict ahead |

> *Either `week_start` or `date` is required per record. Use `week_start` for weekly data.

#### Response shape

```json
{
  "predictions": [
    { "date": "2026-06-23", "percentage_point": 74.52 },
    { "date": "2026-06-30", "percentage_point": 76.18 },
    { "date": "2026-07-07", "percentage_point": 82.95 }
  ]
}
```

#### Example 1 — Weekly history, predict 28 days

**Request:**
```json
{
  "data": [
    { "week_start": "2026-05-12", "occupancy_rate": 42.5 },
    { "week_start": "2026-05-19", "occupancy_rate": 48.7 },
    { "week_start": "2026-05-26", "occupancy_rate": 55.0 },
    { "week_start": "2026-06-02", "occupancy_rate": 60.2 },
    { "week_start": "2026-06-09", "occupancy_rate": 68.3 }
  ],
  "days": 28
}
```

**Response (4 weekly predictions, ~7 days apart):**
```json
{
  "predictions": [
    { "date": "2026-06-23", "percentage_point": 72.50 },
    { "date": "2026-06-30", "percentage_point": 70.11 },
    { "date": "2026-07-07", "percentage_point": 82.95 },
    { "date": "2026-07-14", "percentage_point": 84.30 }
  ]
}
```

#### Example 2 — Short weekly history, predict 14 days

**Request:**
```json
{
  "data": [
    { "week_start": "2026-06-02", "occupancy_rate": 34.1 },
    { "week_start": "2026-06-09", "occupancy_rate": 40.6 }
  ],
  "days": 14
}
```

**Response (2 weekly predictions):**
```json
{
  "predictions": [
    { "date": "2026-06-23", "percentage_point": 45.20 },
    { "date": "2026-06-30", "percentage_point": 51.75 }
  ]
}
```

#### Example 3 — Using `date` keys (daily style), predict 7 days

**Request:**
```json
{
  "data": [
    { "date": "2026-06-09", "occupancy_rate": 43.2 },
    { "date": "2026-06-10", "occupancy_rate": 45.1 },
    { "date": "2026-06-11", "occupancy_rate": 44.8 },
    { "date": "2026-06-12", "occupancy_rate": 42.0 },
    { "date": "2026-06-13", "occupancy_rate": 47.5 }
  ],
  "days": 7
}
```

**Response (1 weekly prediction for 7 days):**
```json
{
  "predictions": [
    { "date": "2026-06-23", "percentage_point": 49.30 }
  ]
}
```

### Using curl (PowerShell, with CSRF)

```powershell
curl.exe -i -c csrf_cookies.txt http://127.0.0.1:8000/
$token = [uri]::UnescapeDataString((Get-Content csrf_cookies.txt | Where-Object { $_ -match 'XSRF-TOKEN' } | ForEach-Object { ($_ -split '\t')[6] }))
$body = '{"data":[{"week_start":"2026-05-12","occupancy_rate":42.5},{"week_start":"2026-05-19","occupancy_rate":48.7},{"week_start":"2026-05-26","occupancy_rate":55.0},{"week_start":"2026-06-02","occupancy_rate":60.2},{"week_start":"2026-06-09","occupancy_rate":68.3}],"days":28}'
Set-Content -Path ai_payload.json -Value $body
curl.exe -X POST http://127.0.0.1:8000/ai/predict -H "Accept: application/json" -H "Content-Type: application/json" -H "X-XSRF-TOKEN: $token" --cookie csrf_cookies.txt --data-binary '@ai_payload.json'
```

### Direct Python workflow

If you want to run the AI scripts directly without the Laravel endpoint:

```powershell
cd ai
..\.venv\Scripts\Activate.ps1

# Train both weekly + daily models (default)
python scripts\train_model.py

# Train only one granularity
python scripts\train_model.py --granularity weekly
python scripts\train_model.py --granularity daily

# Generate predictions
python scripts\predict_occupancy.py --granularity weekly --periods 52
python scripts\predict_occupancy.py --granularity daily --periods 90
```

Daily predictions include a **day-over-day comparison** column showing whether a given day is more or less occupied than the previous day (e.g. "↑ lichte stijging", "→ stabiel"). This lets you quickly see if tomorrow will be busier than today.

### Output files

All output lands under `ai/` relative to the script location:

| Granularity | Trained model | Future predictions |
|---|---|---|
| Weekly | `ai/Models/occupancy_regressor.pkl` | `ai/Reports/future_predictions.csv` |
| Daily | `ai/Models/occupancy_regressor_daily.pkl` | `ai/Reports/future_predictions_daily.csv` |

Metrics and test-set predictions are also written to `ai/Reports/`.
