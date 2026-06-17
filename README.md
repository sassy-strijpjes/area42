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

### Using the AI endpoint

**Endpoint:** `POST http://127.0.0.1:8000/ai/predict`

#### Request shape

| Field                   | Type                | Required | Description                                           |
| ----------------------- | ------------------- | -------- | ----------------------------------------------------- |
| `data`                  | array               | yes      | Array of historical occupancy records (min 1)         |
| `data[].date`           | string (YYYY-MM-DD) | yes      | Date of the occupancy record                          |
| `data[].occupancy_rate` | number              | yes      | Occupancy percentage (0–100)                          |
| `days`                  | integer             | yes      | Number of days to predict ahead                       |

> Input data is always daily. The AI predicts daily occupancy percentages by
> default. To get weekly predictions instead, use the `--granularity weekly`
> flag when running the Python scripts directly.

#### Response shape

```json
{
  "predictions": [
    { "date": "2026-06-18", "percentage_point": 74.52 },
    { "date": "2026-06-19", "percentage_point": 76.18 },
    { "date": "2026-06-20", "percentage_point": 82.95 }
  ]
}
```

#### Example 1 — Daily history, predict 7 days

**Request:**

```json
{
  "data": [
    { "date": "2026-06-09", "occupancy_rate": 43.2 },
    { "date": "2026-06-10", "occupancy_rate": 45.1 },
    { "date": "2026-06-11", "occupancy_rate": 44.8 },
    { "date": "2026-06-12", "occupancy_rate": 42.0 },
    { "date": "2026-06-13", "occupancy_rate": 47.5 },
    ...
  ],
  "days": 7
}
```

**Response (7 daily predictions):**

```json
{
  "predictions": [
    { "date": "2026-06-18", "percentage_point": 44.10 },
    { "date": "2026-06-19", "percentage_point": 44.80 },
    { "date": "2026-06-20", "percentage_point": 45.95 },
    { "date": "2026-06-21", "percentage_point": 46.30 },
    { "date": "2026-06-22", "percentage_point": 47.10 },
    { "date": "2026-06-23", "percentage_point": 48.25 },
    { "date": "2026-06-24", "percentage_point": 49.30 }
  ]
}
```

#### Example 2 — Short daily history, predict 3 days

**Request:**

```json
{
  "data": [
    { "date": "2026-06-15", "occupancy_rate": 34.1 },
    { "date": "2026-06-16", "occupancy_rate": 40.6 },
    ...
  ],
  "days": 3
}
```

**Response (3 daily predictions):**

```json
{
  "predictions": [
    { "date": "2026-06-18", "percentage_point": 42.35 },
    { "date": "2026-06-19", "percentage_point": 46.10 },
    { "date": "2026-06-20", "percentage_point": 50.25 }
  ]
}
```

### Using curl (PowerShell, with CSRF)

```powershell
curl.exe -i -c csrf_cookies.txt http://127.0.0.1:8000/
$token = [uri]::UnescapeDataString((Get-Content csrf_cookies.txt | Where-Object { $_ -match 'XSRF-TOKEN' } | ForEach-Object { ($_ -split '\t')[6] }))
$body = '{"data":[{"date":"2026-06-09","occupancy_rate":43.2},{"date":"2026-06-10","occupancy_rate":45.1},{"date":"2026-06-11","occupancy_rate":44.8},{"date":"2026-06-12","occupancy_rate":42.0},{"date":"2026-06-13","occupancy_rate":47.5}],"days":7}'
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

# Generate predictions (daily is default; weekly can be requested via flag)
python scripts\predict_occupancy.py --granularity daily --periods 90
python scripts\predict_occupancy.py --granularity weekly --periods 52
```

Both daily and weekly predictions output the same columns: a date,
`predicted_occupancy` (percentage, 0–100), and `crowd_level` ("laag",
"normaal", "hoog"). The only difference is the `--granularity` flag, which
controls whether the output is daily (`stay_date`) or weekly (`week_start`).

### Output files

All output lands under `ai/` relative to the script location:

| Granularity | Trained model                             | Future predictions                        |
| ----------- | ----------------------------------------- | ----------------------------------------- |
| Weekly      | `ai/Models/occupancy_regressor.pkl`       | `ai/Reports/future_predictions.csv`       |
| Daily       | `ai/Models/occupancy_regressor_daily.pkl` | `ai/Reports/future_predictions_daily.csv` |

Metrics and test-set predictions are also written to `ai/Reports/`.
