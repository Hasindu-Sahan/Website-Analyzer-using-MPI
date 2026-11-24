import os
import re
import sys
import json
import time
from collections import defaultdict
from bs4 import BeautifulSoup

PROJECT_FOLDER = os.path.dirname(os.path.abspath(__file__))
WEBSITE_FOLDER = os.path.join(PROJECT_FOLDER, "Remake-of-Dr.Neville-Hospital-Web-")
SEQUENTIAL_RESULT = os.path.join(PROJECT_FOLDER, "sequential_result.json")

HTML_EXTENSIONS = {".html", ".htm"}
CSS_EXTENSIONS = {".css"}
JS_EXTENSIONS = {".js", ".mjs", ".cjs"}
PHP_EXTENSIONS = {".php", ".phtml", ".php3", ".php4", ".php5", ".phar", ".inc"}
EXCLUDED_DIRS = {".git", ".idea", ".vscode", "__pycache__", ".venv", "venv"}


def discover_website_files(folder):
    """Find all supported source files recursively; image/media files are excluded."""
    if not os.path.isdir(folder):
        raise FileNotFoundError(f"Website folder not found: {folder}")

    files = {"HTML": [], "CSS": [], "JavaScript": [], "PHP": []}
    for root, dirs, filenames in os.walk(folder):
        dirs[:] = [d for d in dirs if d not in EXCLUDED_DIRS]
        for filename in filenames:
            path = os.path.join(root, filename)
            extension = os.path.splitext(filename)[1].lower()
            category = None
            if extension in HTML_EXTENSIONS:
                category = "HTML"
            elif extension in CSS_EXTENSIONS:
                category = "CSS"
            elif extension in JS_EXTENSIONS:
                category = "JavaScript"
            elif extension in PHP_EXTENSIONS:
                category = "PHP"
            if category:
                files[category].append(os.path.relpath(path, folder))

    for category in files:
        files[category].sort()
    return files


def empty_html_metrics():
    return {"file_count": 0, "file_size": 0, "elements": 0, "headings": 0,
            "links": 0, "images": 0, "forms": 0, "sections": 0, "divs": 0}


def empty_css_metrics():
    return {"file_count": 0, "file_size": 0, "lines": 0, "rules": 0,
            "selectors": 0, "media_queries": 0}


def empty_js_metrics():
    return {"file_count": 0, "file_size": 0, "lines": 0, "functions": 0,
            "arrow_functions": 0, "event_listeners": 0, "variables": 0}


def analyze_html_files(folder, relative_paths):
    result = empty_html_metrics()
    for relative_path in relative_paths:
        path = os.path.join(folder, relative_path)
        with open(path, "r", encoding="utf-8", errors="replace") as file:
            content = file.read()
        soup = BeautifulSoup(content, "html.parser")
        result["file_count"] += 1
        result["file_size"] += os.path.getsize(path)
        result["elements"] += len(soup.find_all())
        result["headings"] += len(soup.find_all(["h1", "h2", "h3", "h4", "h5", "h6"]))
        result["links"] += len(soup.find_all("a"))
        result["images"] += len(soup.find_all("img"))
        result["forms"] += len(soup.find_all("form"))
        result["sections"] += len(soup.find_all("section"))
        result["divs"] += len(soup.find_all("div"))
    return result


def analyze_css_files(folder, relative_paths):
    result = empty_css_metrics()
    for relative_path in relative_paths:
        path = os.path.join(folder, relative_path)
        with open(path, "r", encoding="utf-8", errors="replace") as file:
            css = file.read()
        clean = re.sub(r"/\*.*?\*/", "", css, flags=re.DOTALL)
        result["file_count"] += 1
        result["file_size"] += os.path.getsize(path)
        result["lines"] += len(css.splitlines())
        result["rules"] += clean.count("{")
        result["selectors"] += sum(1 for block in clean.split("{")
                                   if "}" in block and block.split("}")[0].strip())
        result["media_queries"] += len(re.findall(r"@media", clean, flags=re.IGNORECASE))
    return result


def analyze_js_files(folder, relative_paths):
    result = empty_js_metrics()
    for relative_path in relative_paths:
        path = os.path.join(folder, relative_path)
        with open(path, "r", encoding="utf-8", errors="replace") as file:
            javascript = file.read()
        result["file_count"] += 1
        result["file_size"] += os.path.getsize(path)
        result["lines"] += len(javascript.splitlines())
        result["functions"] += len(re.findall(r"\bfunction\s+\w+\s*\(", javascript))
        result["arrow_functions"] += len(re.findall(r"=>", javascript))
        result["event_listeners"] += len(re.findall(r"addEventListener\s*\(", javascript))
        result["variables"] += len(re.findall(r"\b(?:const|let|var)\s+\w+", javascript))
    return result


def analyze_php_files(folder, relative_paths):
    total_size = sum(os.path.getsize(os.path.join(folder, item)) for item in relative_paths)
    return {"file_count": len(relative_paths), "file_size": total_size}


def calculate_percentages(metrics):
    sizes = {
        "HTML": metrics["HTML"]["file_size"],
        "CSS": metrics["CSS"]["file_size"],
        "JavaScript": metrics["JavaScript"]["file_size"],
        "PHP": metrics["PHP"]["file_size"]
    }
    total = sum(sizes.values())
    percentages = {name: (size / total * 100 if total else 0.0)
                   for name, size in sizes.items()}
    return percentages, total


def run_analysis():
    start_total = time.perf_counter()
    scan_start = time.perf_counter()
    files = discover_website_files(WEBSITE_FOLDER)
    scan_time = time.perf_counter() - scan_start

    task_functions = {
        "HTML": analyze_html_files,
        "CSS": analyze_css_files,
        "JavaScript": analyze_js_files
    }
    metrics = {}
    times = {}
    for category, function in task_functions.items():
        task_start = time.perf_counter()
        metrics[category] = function(WEBSITE_FOLDER, files[category])
        times[category.lower() if category != "JavaScript" else "javascript"] = time.perf_counter() - task_start

    php_start = time.perf_counter()
    metrics["PHP"] = analyze_php_files(WEBSITE_FOLDER, files["PHP"])
    times["php"] = time.perf_counter() - php_start

    percentages, total_source_size = calculate_percentages(metrics)
    total_files = sum(len(paths) for paths in files.values())
    total_time = time.perf_counter() - start_total

    return {
        "website": os.path.basename(WEBSITE_FOLDER),
        "website_path": WEBSITE_FOLDER,
        "file_counts": {category: len(paths) for category, paths in files.items()},
        "total_files": total_files,
        "total_source_size": total_source_size,
        "metrics": metrics,
        "file_size_percentages": percentages,
        "times": {"scan": scan_time, **times, "total": total_time}
    }


if __name__ == "__main__":
    try:
        output = run_analysis()
        with open(SEQUENTIAL_RESULT, "w", encoding="utf-8") as file:
            json.dump(output, file, indent=4)
        print("\nSEQUENTIAL WEBSITE ANALYSIS")
        print("Website:", output["website"])
        print("Files scanned:", output["total_files"])
        print("Source-code size:", output["total_source_size"], "bytes")
        print("File-size percentages:", output["file_size_percentages"])
        print("Execution time:", f"{output['times']['total']:.6f} sec")
    except Exception as error:
        print("ERROR:", str(error), file=sys.stderr)
        sys.exit(1)
