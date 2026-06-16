import urllib.request
import os

url = "https://upload.wikimedia.org/wikipedia/commons/b/b3/India-locator-map-blank.svg"
dest = "india_map.svg"

try:
    print(f"Downloading {url} with User-Agent header...")
    req = urllib.request.Request(
        url, 
        headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'}
    )
    with urllib.request.urlopen(req) as response:
        with open(dest, 'wb') as out_file:
            out_file.write(response.read())
    print(f"Saved to {dest}, size: {os.path.getsize(dest)} bytes")
except Exception as e:
    print(f"Error: {e}")
