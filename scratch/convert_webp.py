import os
import glob
from PIL import Image

def convert_to_webp():
    base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "assets", "star-players"))
    print(f"Scanning directory: {base_dir}")
    
    # Supported formats to convert
    extensions = ["*.jpeg", "*.jpg", "*.png"]
    files_to_convert = []
    for ext in extensions:
        files_to_convert.extend(glob.glob(os.path.join(base_dir, "**", ext), recursive=True))
        
    print(f"Found {len(files_to_convert)} image files to convert.")
    
    for file_path in files_to_convert:
        try:
            img = Image.open(file_path)
            # Create webp path
            base, _ = os.path.splitext(file_path)
            webp_path = base + ".webp"
            
            # Save as webp
            img.save(webp_path, "WEBP", quality=85)
            print(f"Converted: {file_path} -> {webp_path}")
            
            # Remove original file
            os.remove(file_path)
        except Exception as e:
            print(f"Failed to convert {file_path}: {e}")

if __name__ == "__main__":
    convert_to_webp()
