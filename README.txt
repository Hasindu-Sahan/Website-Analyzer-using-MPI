TASK PARALLEL WEB PAGE ANALYZER
================================

Changes
-------
- Uses MPI processes (not Python threads).
- Exactly 3 MPI processes are used:
  Process/Core 1 -> HTML analysis
  Process/Core 2 -> CSS analysis
  Process/Core 3 -> JavaScript analysis
- Image analysis has been removed.
- HTML, CSS and JavaScript file-size percentages are calculated.
- Dashboard stores each successful test in test_history.json and displays
  Test, Sequential Time, Parallel Time, Speed Up and Efficiency.

Install Python packages
-----------------------
pip install flask beautifulsoup4 mpi4py

You also need an MPI runtime installed and available as "mpiexec".
On Windows, install Microsoft MPI (MS-MPI) and ensure mpiexec is on PATH.

Run the dashboard
-----------------
python dashboard.py

Open:
http://127.0.0.1:5000

The dashboard starts the sequential program and then launches the parallel
program using 3 MPI processes.

Run the analyzers directly
--------------------------
python sequential.py 1
mpiexec -n 3 python parallel.py 1

Use 2 instead of 1 to analyze Webpage 2.

Notes
-----
- The three MPI ranks are separate processes, not threads. They are assigned
  one analysis task each. Actual CPU-core placement is handled by the MPI
  runtime and operating system.
- File-size percentages are calculated as each file's bytes divided by the
  combined bytes of index.html, style.css and script.js.
- Speedup = sequential total time / parallel wall-clock time.
- Efficiency = (speedup / 3) * 100.
- Test history is stored in test_history.json in this project folder.
