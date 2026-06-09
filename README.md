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
python scripts\train_model.py --input Data\Processed\occupancy_weekly.csv --train-output Data\Training\train_weekly.csv --test-output Data\Test\test_weekly.csv --model-output Models\occupancy_regressor.pkl --metrics-output Reports\metrics.json --predictions-output Reports\predictions.csv --predictions-json-output Reports\predictions.json
python scripts\predict_occupancy.py --model Models\occupancy_regressor.pkl --history Data\Processed\occupancy_weekly.csv --weeks 14 --output Reports\future_predictions.csv
```

### Notes

- The Laravel AI integration writes temporary training input files to `software/storage/app/ai`.
- The Python scripts write model and report output under `ai/Models/`, `ai/Data/`, and `ai/Reports/`.
- If the Python interpreter is not found on the default `PATH`, make sure `AI_PYTHON_BINARY` points to a valid Python executable.
