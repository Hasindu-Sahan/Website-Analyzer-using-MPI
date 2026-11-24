import os
import re
import sys
import json
import time
from mpi4py import MPI
from bs4 import BeautifulSoup

PROJECT_FOLDER = os.path.dirname(os.path.abspath(__file__))
WEBSITE_FOLDER = os.path.join(PROJECT_FOLDER, "Remake-of-Dr.Neville-Hospital-Web-")
PARALLEL_RESULT = os.path.join(PROJECT_FOLDER, "parallel_result.json")

HTML_EXTENSIONS = {".html", ".htm"}
CSS_EXTENSIONS = {".css"}
JS_EXTENSIONS = {".js", ".mjs", ".cjs"}
PHP_EXTENSIONS = {".php", ".phtml", ".php3", ".php4", ".php5", ".phar", ".inc"}
EXCLUDED_DIRS = {".git", ".idea", ".vscode", "__pycache__", ".venv", "venv"}

comm = MPI.COMM_WORLD
rank = comm.Get_rank()
size = comm.Get_size()
processor_name = MPI.Get_processor_name()
pid = os.getpid()


def discover_website_files(folder):
    if not os.path.isdir(folder):
        raise FileNotFoundError(f"Website folder not found: {folder}")
    files = {"HTML": [], "CSS": [], "JavaScript": [], "PHP": []}
    for root, dirs, filenames in os.walk(folder):
        dirs[:] = [d for d in dirs if d not in EXCLUDED_DIRS]
        for filename in filenames:
            path = os.path.join(root, filename)
            extension = os.path.splitext(filename)[1].lower()
            if extension in HTML_EXTENSIONS:
                category = "HTML"
            elif extension in CSS_EXTENSIONS:
                category = "CSS"
            elif extension in JS_EXTENSIONS:
                category = "JavaScript"
            elif extension in PHP_EXTENSIONS:
                category = "PHP"
            else:
                continue
            files[category].append(os.path.relpath(path, folder))
    for category in files:
        files[category].sort()
    return files


def empty_metrics(category):
    if category == "HTML":
        return {"file_count": 0, "file_size": 0, "elements": 0, "headings": 0,
                "links": 0, "images": 0, "forms": 0, "sections": 0, "divs": 0}
    if category == "CSS":
        return {"file_count": 0, "file_size": 0, "lines": 0, "rules": 0,
                "selectors": 0, "media_queries": 0}
    if category == "JavaScript":
        return {"file_count": 0, "file_size": 0, "lines": 0, "functions": 0,
                "arrow_functions": 0, "event_listeners": 0, "variables": 0}
    return {"file_count": 0, "file_size": 0}


def analyze_files(category, folder, relative_paths):
    result = empty_metrics(category)
    for relative_path in relative_paths:
        path = os.path.join(folder, relative_path)
        with open(path, "r", encoding="utf-8", errors="replace") as file:
            content = file.read()
        result["file_count"] += 1
        result["file_size"] += os.path.getsize(path)

        if category == "HTML":
            soup = BeautifulSoup(content, "html.parser")
            result["elements"] += len(soup.find_all())
            result["headings"] += len(soup.find_all(["h1", "h2", "h3", "h4", "h5", "h6"]))
            result["links"] += len(soup.find_all("a"))
            result["images"] += len(soup.find_all("img"))
            result["forms"] += len(soup.find_all("form"))
            result["sections"] += len(soup.find_all("section"))
            result["divs"] += len(soup.find_all("div"))
        elif category == "CSS":
            clean = re.sub(r"/\*.*?\*/", "", content, flags=re.DOTALL)
            result["lines"] += len(content.splitlines())
            result["rules"] += clean.count("{")
            result["selectors"] += sum(1 for block in clean.split("{")
                                       if "}" in block and block.split("}")[0].strip())
            result["media_queries"] += len(re.findall(r"@media", clean, flags=re.IGNORECASE))
        elif category == "JavaScript":
            result["lines"] += len(content.splitlines())
            result["functions"] += len(re.findall(r"\bfunction\s+\w+\s*\(", content))
            result["arrow_functions"] += len(re.findall(r"=>", content))
            result["event_listeners"] += len(re.findall(r"addEventListener\s*\(", content))
            result["variables"] += len(re.findall(r"\b(?:const|let|var)\s+\w+", content))
        elif category == "PHP":
            result["lines"] += len(content.splitlines())
    return result


def merge_metrics(category, partial_results):
    merged = empty_metrics(category)
    for partial in partial_results:
        for key, value in partial.items():
            merged[key] += value
    return merged


def calculate_percentages(metrics):
    sizes = {
        "HTML": metrics["HTML"]["file_size"],
        "CSS": metrics["CSS"]["file_size"],
        "JavaScript": metrics["JavaScript"]["file_size"],
        "PHP": metrics["PHP"]["file_size"]
    }
    total = sum(sizes.values())
    percentages = {name: (value / total * 100 if total else 0.0)
                   for name, value in sizes.items()}
    return percentages, total


# Every rank is assigned a task type cyclically. For 2 ranks, rank 0 handles
# HTML + JavaScript and rank 1 handles CSS, preserving the earlier mapping.
TASK_NAMES = ["HTML", "CSS", "JavaScript"]
if size < len(TASK_NAMES):
    rank_categories = [name for index, name in enumerate(TASK_NAMES) if index % size == rank]
else:
    rank_categories = [TASK_NAMES[rank % len(TASK_NAMES)]]

local_process_info = {
    "rank": rank,
    "pid": pid,
    "processor": processor_name,
    "tasks": rank_categories
}
all_process_info = comm.gather(local_process_info, root=0)

# Include directory scanning and manifest distribution in the parallel wall time.
comm.Barrier()
parallel_start = MPI.Wtime()
if rank == 0:
    try:
        manifest = discover_website_files(WEBSITE_FOLDER)
        scan_error = None
    except Exception as error:
        manifest = None
        scan_error = str(error)
else:
    manifest = None
    scan_error = None
manifest, scan_error = comm.bcast((manifest, scan_error), root=0)

if scan_error:
    if rank == 0:
        print("ERROR:", scan_error, file=sys.stderr)
    comm.Abort(1)

local_results = []
for category in rank_categories:
    category_ranks = [process_rank for process_rank in range(size)
                      if category in ([name for index, name in enumerate(TASK_NAMES)
                                       if index % size == process_rank] if size < 3
                                      else [TASK_NAMES[process_rank % 3]])]
    group_index = category_ranks.index(rank)
    assigned_files = manifest[category][group_index::len(category_ranks)]
    task_start = MPI.Wtime()
    partial_metrics = analyze_files(category, WEBSITE_FOLDER, assigned_files)
    task_elapsed = MPI.Wtime() - task_start
    local_results.append({"category": category, "rank": rank,
                          "metrics": partial_metrics, "time": task_elapsed,
                          "assigned_file_count": len(assigned_files)})

# Include PHP file-size inventory in the timed parallel phase. PHP is kept
# separate from the three measured HTML/CSS/JavaScript task groups.
if rank == 0:
    php_items = manifest["PHP"]
    php_metrics = {
        "file_count": len(php_items),
        "file_size": sum(os.path.getsize(os.path.join(WEBSITE_FOLDER, item))
                          for item in php_items)
    }

comm.Barrier()
local_elapsed = MPI.Wtime() - parallel_start
parallel_time = comm.allreduce(local_elapsed, op=MPI.MAX)
all_partial_results = comm.gather(local_results, root=0)

if rank == 0:
    flattened = [item for rank_items in all_partial_results for item in rank_items]
    metrics = {}
    task_times = {}
    task_ranks = {}
    for category in TASK_NAMES:
        category_items = [item for item in flattened if item["category"] == category]
        metrics[category] = merge_metrics(category, [item["metrics"] for item in category_items])
        task_times[category] = max((item["time"] for item in category_items), default=0.0)
        task_ranks[category] = [item["rank"] for item in category_items]

    # PHP is reported separately and contributes to the percentage denominator.
    metrics["PHP"] = php_metrics

    percentages, total_source_size = calculate_percentages(metrics)
    total_files = sum(len(paths) for paths in manifest.values())
    output = {
        "website": os.path.basename(WEBSITE_FOLDER),
        "file_counts": {category: len(paths) for category, paths in manifest.items()},
        "total_files": total_files,
        "total_source_size": total_source_size,
        "metrics": metrics,
        "file_size_percentages": percentages,
        "parallel_time": parallel_time,
        "process_count": size,
        "processes": sorted(all_process_info, key=lambda item: item["rank"]),
        "tasks": {
            category: {"time": task_times[category], "ranks": task_ranks[category],
                       "result": metrics[category]}
            for category in TASK_NAMES
        }
    }
    with open(PARALLEL_RESULT, "w", encoding="utf-8") as file:
        json.dump(output, file, indent=4)

    print("\nTASK-PARALLEL WHOLE WEBSITE ANALYSIS")
    print("Website:", output["website"])
    print("MPI processes:", size)
    print("Files scanned:", total_files)
    print("File-size percentages:", percentages)
    for category in TASK_NAMES:
        print(f"{category}: ranks {task_ranks[category]}, {task_times[category]:.6f} sec")
    print(f"Total parallel wall-clock time: {parallel_time:.6f} sec")
