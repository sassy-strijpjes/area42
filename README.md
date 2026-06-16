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

The route expects a `POST` request with JSON in this shape:

```json
{
  "data": [
    { "week_start": "2025-01-06", "occupancy_rate": 42.5 },
    { "week_start": "2025-01-13", "occupancy_rate": 48.7 }
  ],
  "days": 14
}
```

Example with `curl` (requires CSRF token support because the route is defined in `web.php`):

```powershell
curl.exe -i -c csrf_cookies.txt http://127.0.0.1:8000/
$token = [uri]::UnescapeDataString((Get-Content csrf_cookies.txt | Where-Object { $_ -match 'XSRF-TOKEN' } | ForEach-Object { ($_ -split '\t')[6] }))
$body = '{"data":[{"week_start":"2025-01-06","occupancy_rate":42.5},{"week_start":"2025-01-13","occupancy_rate":48.7}],"days":14}'
Set-Content -Path ai_payload.json -Value $body
curl.exe -X POST http://127.0.0.1:8000/ai/predict -H "Accept: application/json" -H "Content-Type: application/json" -H "X-XSRF-TOKEN: $token" --cookie csrf_cookies.txt --data-binary '@ai_payload.json'
```

The endpoint returns JSON with `predictions` containing `date` and `percentage_point` values.

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
