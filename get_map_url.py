import urllib.request
import re
import os

url = "https://commons.wikimedia.org/wiki/File:India-locator-map-blank.svg"
req = urllib.request.Request(
    url, 
    headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko)'}
)

try:
    print(f"Fetching Commons page {url}...")
    with urllib.request.urlopen(req) as response:
        html = response.read().decode('utf-8')
    
    # Search for the direct upload link
    # Pattern to look for: https://upload.wikimedia.org/wikipedia/commons/.../India-locator-map-blank.svg
    match = re.search(r'https://upload.wikimedia.org/wikipedia/commons/[^"\']+/India-locator-map-blank\.svg', html)
    if match:
        svg_url = match.group(0)
        print(f"Found SVG URL: {svg_url}")
        
        # Download the SVG file
        req_svg = urllib.request.Request(
            svg_url,
            headers={'User-Agent': 'Mozilla/5.0'}
        )
        print("Downloading SVG...")
        with urllib.request.urlopen(req_svg) as response_svg:
            with open("india_map.svg", 'wb') as out_file:
                out_file.write(response_svg.read())
        print(f"Saved to india_map.svg, size: {os.path.getsize('india_map.svg')} bytes")
    else:
        print("Error: Could not find direct SVG URL in HTML.")
except Exception as e:
    print(f"Error: {e}")
