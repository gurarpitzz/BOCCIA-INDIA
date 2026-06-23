import os
from PIL import Image

def analyze_directory(path):
    image_extensions = ('.png', '.jpg', '.jpeg', '.webp')
    results = []
    for root, dirs, files in os.walk(path):
        # Skip node_modules or .git
        if 'node_modules' in root or '.git' in root:
            continue
        for file in files:
            if file.lower().endswith(image_extensions):
                full_path = os.path.join(root, file)
                size_bytes = os.path.getsize(full_path)
                try:
                    with Image.open(full_path) as img:
                        width, height = img.size
                        format_name = img.format
                except Exception as e:
                    width, height = 0, 0
                    format_name = "Unknown"
                results.append({
                    'path': os.path.relpath(full_path, path),
                    'size_kb': size_bytes / 1024.0,
                    'width': width,
                    'height': height,
                    'format': format_name
                })
    return results

if __name__ == '__main__':
    images = analyze_directory('.')
    # Sort by size descending
    images.sort(key=lambda x: x['size_kb'], reverse=True)
    print(f"{'Path':<50} | {'Size (KB)':<10} | {'Dimensions':<12} | {'Format':<6}")
    print("-" * 88)
    for img in images[:30]:
        dims = f"{img['width']}x{img['height']}"
        print(f"{img['path']:<50} | {img['size_kb']:<10.2f} | {dims:<12} | {img['format']:<6}")
