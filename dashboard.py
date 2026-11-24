from flask import Flask, render_template_string, request
import subprocess
import sys
import os
import json

app = Flask(__name__)
PROJECT_FOLDER = os.path.dirname(os.path.abspath(__file__))
WEBSITE_FOLDER = os.path.join(PROJECT_FOLDER, "Remake-of-Dr.Neville-Hospital-Web-")
SEQUENTIAL_SCRIPT = os.path.join(PROJECT_FOLDER, "sequential.py")
PARALLEL_SCRIPT = os.path.join(PROJECT_FOLDER, "parallel.py")
SEQUENTIAL_RESULT = os.path.join(PROJECT_FOLDER, "sequential_result.json")
PARALLEL_RESULT = os.path.join(PROJECT_FOLDER, "parallel_result.json")
HISTORY_PATH = os.path.join(PROJECT_FOLDER, "test_history.json")


def load_json(path):
    with open(path, "r", encoding="utf-8") as file:
        return json.load(file)


def run_sequential():
    try:
        result = subprocess.run([sys.executable, SEQUENTIAL_SCRIPT], cwd=PROJECT_FOLDER,
                                capture_output=True, text=True, encoding="utf-8",
                                errors="replace", timeout=300)
        if result.returncode != 0:
            return {"success": False, "error": "Sequential analysis failed:\n\n" + result.stdout + "\n" + result.stderr}
        if not os.path.exists(SEQUENTIAL_RESULT):
            return {"success": False, "error": "sequential_result.json was not created."}
        return {"success": True, "data": load_json(SEQUENTIAL_RESULT)}
    except subprocess.TimeoutExpired:
        return {"success": False, "error": "Sequential analysis timed out after 300 seconds."}
    except Exception as error:
        return {"success": False, "error": str(error)}


def run_parallel(process_count):
    try:
        result = subprocess.run(["mpiexec", "-n", str(process_count), sys.executable,
                                 PARALLEL_SCRIPT], cwd=PROJECT_FOLDER,
                                capture_output=True, text=True, encoding="utf-8",
                                errors="replace", timeout=300)
        if result.returncode != 0:
            return {"success": False, "error": "Task-parallel analysis failed:\n\n" + result.stdout + "\n" + result.stderr}
        if not os.path.exists(PARALLEL_RESULT):
            return {"success": False, "error": "parallel_result.json was not created."}
        return {"success": True, "data": load_json(PARALLEL_RESULT)}
    except subprocess.TimeoutExpired:
        return {"success": False, "error": "MPI analysis timed out after 300 seconds."}
    except FileNotFoundError:
        return {"success": False, "error": "mpiexec was not found. Check that MPI is installed and available in PATH."}
    except Exception as error:
        return {"success": False, "error": str(error)}


@app.route("/", methods=["GET", "POST"])
def dashboard():
    selected_process_count = 4
    sequential = parallel = None
    process_distribution = []
    speedup = efficiency = None
    error = None
    history = []

    if not os.path.isdir(WEBSITE_FOLDER):
        error = "Website folder not found. Place 'Remake-of-Dr.Neville-Hospital-Web-' beside dashboard.py."

    if os.path.exists(HISTORY_PATH):
        try:
            history = load_json(HISTORY_PATH)
        except Exception:
            history = []

    if error is None and request.method == "POST":
        try:
            selected_process_count = int(request.form.get("process_count", 4))
        except (TypeError, ValueError):
            selected_process_count = 4
        if selected_process_count not in [2, 3, 4, 8]:
            selected_process_count = 4

        sequential_result = run_sequential()
        if not sequential_result["success"]:
            error = sequential_result["error"]
        else:
            sequential = sequential_result["data"]

        if error is None:
            parallel_result = run_parallel(selected_process_count)
            if not parallel_result["success"]:
                error = parallel_result["error"]
            else:
                parallel = parallel_result["data"]
                process_distribution = parallel.get("processes", [])

        if error is None:
            if sequential.get("website") != parallel.get("website"):
                error = "Sequential and MPI results refer to different website folders."
            elif parallel.get("process_count") != selected_process_count:
                error = "MPI process-count verification failed."
            elif sequential.get("file_counts") != parallel.get("file_counts"):
                error = "Sequential and MPI analyses scanned different file counts."

        if error is None:
            sequential_total = float(sequential["times"]["total"])
            parallel_total = float(parallel["parallel_time"])
            if sequential_total <= 0 or parallel_total <= 0:
                error = "Invalid execution time values."
            else:
                speedup = sequential_total / parallel_total
                efficiency = speedup / selected_process_count * 100

        if error is None:
            history.append({"test": len(history) + 1,
                            "process_count": selected_process_count,
                            "total_files": sequential["total_files"],
                            "sequential_time": sequential["times"]["total"],
                            "parallel_time": parallel["parallel_time"],
                            "speedup": speedup, "efficiency": efficiency})
            with open(HISTORY_PATH, "w", encoding="utf-8") as file:
                json.dump(history, file, indent=4)

    return render_template_string(DASHBOARD_HTML, history=history,
                                  selected_process_count=selected_process_count,
                                  sequential=sequential, parallel=parallel,
                                  process_distribution=process_distribution,
                                  speedup=speedup, efficiency=efficiency, error=error)


DASHBOARD_HTML = r"""
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Whole Website Analyzer</title>
<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f4f7fb;

    color: #172033;
}


/* ============================================================
   HEADER
   ============================================================ */

.header {

    background: #172033;

    color: white;

    text-align: center;

    padding: 35px 20px 30px;
}

.header h1 {

    margin: 0;

    font-size: 32px;

    letter-spacing: 1px;
}

.header p {

    margin: 8px 0 0;

    color: #b8c2d6;

    font-size: 16px;
}


/* ============================================================
   CONTROL
   ============================================================ */

.control-section {

    background: white;

    padding: 25px;

    border-bottom: 1px solid #e1e6ef;

    text-align: center;
}

.control-section form {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 12px;

    flex-wrap: wrap;
}

.control-section label {

    font-weight: bold;
}

select {

    padding: 11px 16px;

    border: 1px solid #ccd4e0;

    border-radius: 7px;

    font-size: 15px;

    background: white;
}

button {

    padding: 11px 22px;

    border: none;

    border-radius: 7px;

    background: #2563eb;

    color: white;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;
}

button:hover {

    background: #1d4ed8;
}


/* ============================================================
   MAIN
   ============================================================ */

.container {

    max-width: 1200px;

    margin: 0 auto;

    padding: 35px 20px 50px;
}

.section-title {

    text-align: center;

    font-size: 22px;

    margin: 0 0 25px;
}


/* ============================================================
   EXECUTION
   ============================================================ */

.execution-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 25px;
}

.card {

    background: white;

    border: 1px solid #e1e6ef;

    border-radius: 12px;

    padding: 25px;

    box-shadow:
        0 4px 15px rgba(0,0,0,0.05);
}

.card h3 {

    margin-top: 0;

    text-align: center;

    font-size: 20px;
}

.task {

    margin: 22px 0;
}

.task-header {

    display: flex;

    justify-content: space-between;

    margin-bottom: 7px;

    font-size: 14px;
}

.bar-container {

    width: 100%;

    height: 13px;

    background: #e8edf5;

    border-radius: 20px;

    overflow: hidden;
}

.bar {

    height: 100%;

    background: #2563eb;

    border-radius: 20px;
}

.parallel-card .bar {

    background: #16a34a;
}

.total {

    border-top: 1px solid #e1e6ef;

    margin-top: 25px;

    padding-top: 20px;

    display: flex;

    justify-content: space-between;

    font-size: 18px;

    font-weight: bold;
}


/* ============================================================
   PERFORMANCE
   ============================================================ */

.performance {

    margin-top: 35px;
}

.performance-box {

    background: white;

    border: 1px solid #e1e6ef;

    border-radius: 12px;

    padding: 25px;

    box-shadow:
        0 4px 15px rgba(0,0,0,0.05);
}

.performance-row {

    margin: 20px 0;
}

.performance-label {

    display: flex;

    justify-content: space-between;

    margin-bottom: 8px;

    font-weight: bold;
}

.performance-bar {

    height: 20px;

    background: #e8edf5;

    border-radius: 20px;

    overflow: hidden;
}

.performance-fill {

    height: 100%;

    background: #2563eb;

    border-radius: 20px;
}

.performance-fill.parallel {

    background: #16a34a;
}


/* ============================================================
   METRICS
   ============================================================ */

.metrics {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 25px;

    margin-top: 25px;
}

.metric {

    background: white;

    border: 1px solid #e1e6ef;

    border-radius: 12px;

    text-align: center;

    padding: 25px;

    box-shadow:
        0 4px 15px rgba(0,0,0,0.05);
}

.metric-title {

    font-size: 15px;

    color: #64748b;

    margin-bottom: 10px;
}

.metric-value {

    font-size: 32px;

    font-weight: bold;

    color: #2563eb;
}


/* ============================================================
   MPI PROCESS DISTRIBUTION
   ============================================================ */

.process-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.process-card {
    background: white;
    border: 1px solid #dbe3ee;
    border-left: 5px solid #16a34a;
    border-radius: 10px;
    padding: 18px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.04);
}

.process-card.idle {
    border-left-color: #94a3b8;
    background: #f8fafc;
}

.process-card h4 {
    margin: 0 0 10px;
    font-size: 17px;
}

.process-meta {
    color: #64748b;
    font-size: 12px;
    margin: 5px 0;
    overflow-wrap: anywhere;
}

.process-task {
    display: inline-block;
    margin: 5px 5px 2px 0;
    padding: 6px 10px;
    border-radius: 15px;
    background: #e8f1ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: bold;
}

.process-status {
    display: inline-block;
    margin-top: 10px;
    padding: 5px 9px;
    border-radius: 12px;
    background: #dcfce7;
    color: #166534;
    font-size: 11px;
    font-weight: bold;
}

.process-card.idle .process-status {
    background: #e2e8f0;
    color: #475569;
}

.process-note {
    margin-top: 14px;
    color: #64748b;
    font-size: 13px;
    line-height: 1.5;
}

/* ============================================================
   ERROR
   ============================================================ */

.error {

    background: #fee2e2;

    color: #991b1b;

    padding: 18px;

    border-radius: 8px;

    margin-bottom: 25px;

    white-space: pre-wrap;

    font-family: monospace;
}


/* ============================================================
   PREVIEW
   ============================================================ */

.preview {

    margin-top: 35px;
}

.preview iframe {

    width: 100%;

    height: 600px;

    border: 1px solid #d7deea;

    border-radius: 12px;

    background: white;
}


/* ============================================================
   EMPTY
   ============================================================ */

.empty {

    text-align: center;

    background: white;

    padding: 60px 20px;

    border-radius: 12px;

    border: 1px solid #e1e6ef;

    color: #64748b;
}


/* ============================================================
   RESPONSIVE
   ============================================================ */

@media (max-width: 800px) {

    .execution-grid {

        grid-template-columns: 1fr;
    }

    .metrics {

        grid-template-columns: 1fr;
    }

    .header h1 {

        font-size: 24px;
    }
}


</style>
</head>
<body>
<div class="header">
  <h1>WHOLE WEBSITE ANALYZER</h1>
  <p>Task Parallelism Performance Dashboard</p>
</div>
<div class="control-section">
  <form method="POST">
    <label>Number of Processes:</label>
    <select name="process_count">
      {% for count in [2, 3, 4, 8] %}
      <option value="{{ count }}" {% if selected_process_count == count %}selected{% endif %}>{{ count }}</option>
      {% endfor %}
    </select>
    <button type="submit">RUN ANALYSIS</button>
  </form>
  <p style="color:#64748b;margin:14px 0 0;">Website: Remake-of-Dr.Neville-Hospital-Web-</p>
</div>
<div class="container">
{% if error %}<div class="error">{{ error }}</div>{% endif %}
{% if sequential and parallel and speedup is not none and efficiency is not none and not error %}
<h2 class="section-title">WEBSITE OVERVIEW</h2>
<div class="metrics">
  <div class="metric"><div class="metric-title">TOTAL SOURCE FILES</div><div class="metric-value">{{ sequential.total_files }}</div></div>
  <div class="metric"><div class="metric-title">TOTAL SOURCE-CODE SIZE</div><div class="metric-value">{{ "%.2f"|format(sequential.total_source_size / 1024) }} KB</div></div>
</div>
<h2 class="section-title" style="margin-top:35px;">EXECUTION TIME ANALYSIS</h2>
<div class="execution-grid">
  <div class="card">
    <h3>SEQUENTIAL</h3>
    {% for key, label in [('html','HTML'),('css','CSS'),('javascript','JavaScript')] %}
    <div class="task"><div class="task-header"><span>{{ label }} Analyzing</span><span>{{ "%.4f"|format(sequential.times[key]) }} sec</span></div>
      <div class="bar-container"><div class="bar" style="width:{{ (sequential.times[key] / sequential.times.total * 100) if sequential.times.total else 0 }}%"></div></div>
    </div>
    {% endfor %}
    <div class="total"><span>TOTAL TIME</span><span>{{ "%.4f"|format(sequential.times.total) }} sec</span></div>
  </div>
  <div class="card parallel-card">
    <h3>TASK PARALLEL</h3>
    {% for key, label in [('HTML','HTML'),('CSS','CSS'),('JavaScript','JavaScript')] %}
    <div class="task"><div class="task-header"><span>{{ label }} Analyzing</span><span>{{ "%.4f"|format(parallel.tasks[key].time) }} sec</span></div>
      <div class="bar-container"><div class="bar" style="width:{{ (parallel.tasks[key].time / parallel.parallel_time * 100) if parallel.parallel_time else 0 }}%"></div></div>
    </div>
    {% endfor %}
    <div class="total"><span>TOTAL TIME</span><span>{{ "%.4f"|format(parallel.parallel_time) }} sec</span></div>
  </div>
</div>
<div class="performance">
  <h2 class="section-title">MPI PROCESS DISTRIBUTION</h2>
  <div class="performance-box">
    <p style="text-align:center;color:#64748b;margin-top:0;">Each card represents one MPI process and its assigned website file-processing task.</p>
    <div class="process-grid">
      {% for process in process_distribution %}
      <div class="process-card {% if not process.tasks %}idle{% endif %}">
        <h4>Process {{ process.rank }}</h4>
        <div class="process-meta"><strong>PID:</strong> {{ process.pid }}</div>
        <div class="process-meta"><strong>Processor:</strong> {{ process.processor }}</div>
        {% for task in process.tasks %}<span class="process-task">{{ task }}</span>{% endfor %}
        <span class="process-status">{% if process.tasks %}ACTIVE{% else %}IDLE{% endif %}</span>
      </div>
      {% endfor %}
    </div>
    <p class="process-note">Files belonging to the same category are divided among the MPI ranks assigned to that category. PHP files are included in the source-size percentages but are not one of the three MPI analysis tasks. MPI rank is a process identifier, not a physical CPU-core identifier.</p>
  </div>
</div>
<div class="performance">
  <h2 class="section-title">WHOLE WEBSITE SOURCE FILE SIZE CONTRIBUTION</h2>
  <div class="performance-box">
    <p style="color:#64748b;text-align:center;">Percentages are based on the combined size of HTML, CSS, JavaScript and PHP source files. Images and other binary assets are excluded.</p>
    {% for label, value in sequential.file_size_percentages.items() %}
    <div class="performance-row"><div class="performance-label"><span>{{ label }}</span><span>{{ "%.2f"|format(value) }}%</span></div>
      <div class="performance-bar"><div class="performance-fill" style="width:{{ value }}%"></div></div>
    </div>
    {% endfor %}
    <div class="metrics" style="margin-top:15px;">
      {% for category, count in sequential.file_counts.items() %}
      <div class="metric"><div class="metric-title">{{ category }} FILES</div><div class="metric-value">{{ count }}</div></div>
      {% endfor %}
    </div>
  </div>
</div>
<div class="performance">
  <h2 class="section-title">PERFORMANCE</h2>
  <div class="performance-box">
    <div class="performance-row"><div class="performance-label"><span>Sequential</span><span>{{ "%.4f"|format(sequential.times.total) }} sec</span></div><div class="performance-bar"><div class="performance-fill" style="width:100%"></div></div></div>
    <div class="performance-row"><div class="performance-label"><span>Task Parallel</span><span>{{ "%.4f"|format(parallel.parallel_time) }} sec</span></div><div class="performance-bar"><div class="performance-fill parallel" style="width:{{ (parallel.parallel_time / sequential.times.total * 100) if sequential.times.total else 0 }}%"></div></div></div>
  </div>
</div>
<div class="metrics">
  <div class="metric"><div class="metric-title">SPEEDUP</div><div class="metric-value">{{ "%.2f"|format(speedup) }} ×</div></div>
  <div class="metric"><div class="metric-title">EFFICIENCY</div><div class="metric-value">{{ "%.1f"|format(efficiency) }}%</div></div>
</div>
<div class="performance">
  <h2 class="section-title">TEST RESULTS HISTORY</h2>
  <div class="performance-box" style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;text-align:center;">
      <thead><tr style="border-bottom:2px solid #e1e6ef;">
        <th style="padding:12px;">Test</th><th style="padding:12px;">Processes</th><th style="padding:12px;">Files</th><th style="padding:12px;">Sequential Time (sec)</th><th style="padding:12px;">Parallel Time (sec)</th><th style="padding:12px;">Speed Up</th><th style="padding:12px;">Efficiency</th>
      </tr></thead><tbody>
      {% for item in history %}<tr style="border-bottom:1px solid #e1e6ef;">
        <td style="padding:12px;">{{ item.test }}</td><td style="padding:12px;">{{ item.process_count }}</td><td style="padding:12px;">{{ item.total_files }}</td><td style="padding:12px;">{{ "%.4f"|format(item.sequential_time) }}</td><td style="padding:12px;">{{ "%.4f"|format(item.parallel_time) }}</td><td style="padding:12px;">{{ "%.2f"|format(item.speedup) }}x</td><td style="padding:12px;">{{ "%.1f"|format(item.efficiency) }}%</td>
      </tr>{% endfor %}
      {% if not history %}<tr><td colspan="7" style="padding:15px;">No test results yet.</td></tr>{% endif %}
      </tbody>
    </table>
  </div>
</div>
{% else %}
<div class="empty"><h3>Ready for Analysis</h3><p>Choose the number of MPI processes and click <strong>RUN ANALYSIS</strong> to analyze the complete hospital website.</p></div>
{% endif %}
</div>
</body>
</html>
"""

if __name__ == "__main__":
    print("\nWHOLE WEBSITE TASK PARALLEL ANALYZER")
    print("Website folder:", WEBSITE_FOLDER)
    print("Dashboard: http://127.0.0.1:5000")
    app.run(host="127.0.0.1", port=5000, debug=False)
