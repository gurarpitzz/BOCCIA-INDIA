import os
import re
from PIL import Image

def resize_and_convert_to_webp(src_path, dest_path, max_width=None, quality=75):
    print(f"Optimizing: {src_path} -> {dest_path}")
    try:
        with Image.open(src_path) as img:
            # Convert palette or RGBA to RGB if needed unless webp supports it
            # Webp supports RGBA, so we keep mode
            if max_width and img.width > max_width:
                w_percent = (max_width / float(img.width))
                h_size = int((float(img.height) * float(w_percent)))
                img = img.resize((max_width, h_size), Image.Resampling.LANCZOS)
            
            # Ensure output dir exists
            os.makedirs(os.path.dirname(dest_path), exist_ok=True)
            img.save(dest_path, format="WEBP", quality=quality, method=6)
            
            orig_size = os.path.getsize(src_path) / 1024.0
            new_size = os.path.getsize(dest_path) / 1024.0
            print(f"  Success: {orig_size:.1f} KB -> {new_size:.1f} KB (Saved {orig_size - new_size:.1f} KB)")
            return True
    except Exception as e:
        print(f"  Error processing {src_path}: {e}")
        return False

def optimize_jpeg_inplace(path, max_width=1200, quality=75):
    print(f"Optimizing JPEG in-place: {path}")
    try:
        with Image.open(path) as img:
            if img.width > max_width:
                w_percent = (max_width / float(img.width))
                h_size = int((float(img.height) * float(w_percent)))
                img = img.resize((max_width, h_size), Image.Resampling.LANCZOS)
            img.save(path, format="JPEG", quality=quality, optimize=True)
            print(f"  Success: compressed in-place.")
            return True
    except Exception as e:
        print(f"  Error processing {path}: {e}")
        return False

# Mapping of old paths (relative) to new WebP paths (relative) and max widths
image_map = {
    # Backgrounds & Large Graphics
    'bg.png': ('bg.webp', 1920),
    'bg schedule.png': ('bg_schedule.webp', 1600),
    'footer.png': ('footer.webp', 1920),
    'login bg.png': ('login_bg.webp', 1600),
    'news bg.png': ('news_bg.webp', 1600),
    'animation.png': ('animation.webp', 800),
    'PCI.png': ('PCI.webp', 300),
    'PCI .png': ('PCI.webp', 300),
    
    # About Boccia Backgrounds
    'about boccia/why boccia matter BG.png': ('about boccia/why_boccia_matter_BG.webp', 1600),
    'about boccia/hero bg.png': ('about boccia/hero_bg.webp', 1920),
    'about boccia/overview bg.png': ('about boccia/overview_bg.webp', 1920),
    'about boccia/NATIONAL IMPRINT bg.png': ('about boccia/NATIONAL_IMPRINT_bg.webp', 1600),
    'about boccia/Watch & Learn bg.png': ('about boccia/Watch_Learn_bg.webp', 1600),
    
    # Board
    'board/board bg.png': ('board/board_bg.webp', 1600),
    
    # Logos
    'logos/logo_msje.png': ('logos/logo_msje.webp', 300),
    'logos/Full Logo World Boccia.webp': ('logos/Full Logo World Boccia.webp', 400), # Note: kept name but converting to actual webp format
    'logos/world boccia logo.png': ('logos/world_boccia_logo.webp', 400),
    'Full Logo World Boccia.webp': ('Full Logo World Boccia.webp', 400),
    'logos/PCI.png': ('logos/PCI.webp', 300),
}

# Directories to search and replace text in
text_extensions = ('.php', '.css', '.html', '.js', '.sql')

def update_references(root_dir, replacement_map):
    print("Updating code references...")
    for root, dirs, files in os.walk(root_dir):
        # Skip node_modules, .git, or archives
        if any(p in root for p in ['node_modules', '.git', 'BSFI_Website_Revamp_Assets']):
            continue
        for file in files:
            if file.lower().endswith(text_extensions):
                file_path = os.path.join(root, file)
                try:
                    with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                        content = f.read()
                    
                    original_content = content
                    for src, (dest, _) in replacement_map.items():
                        # Replace exact string matches
                        # Match both base name and full/partial paths
                        src_base = os.path.basename(src)
                        dest_base = os.path.basename(dest)
                        
                        # Replace occurrences of the exact path or filename
                        content = content.replace(src, dest)
                        if src_base != src:
                            content = content.replace(src_base, dest_base)
                            
                    if content != original_content:
                        with open(file_path, 'w', encoding='utf-8') as f:
                            f.write(content)
                        print(f"  Updated references in: {os.path.relpath(file_path, root_dir)}")
                except Exception as e:
                    print(f"  Error updating file {file_path}: {e}")

if __name__ == '__main__':
    # 1. Optimize defined mappings
    for src, (dest, max_w) in image_map.items():
        if os.path.exists(src):
            success = resize_and_convert_to_webp(src, dest, max_width=max_w, quality=75)
            if success and src != dest and os.path.exists(src):
                # Remove original PNG/JPG file to free space and prevent usage
                os.remove(src)
                print(f"  Removed original file: {src}")

    # 2. Optimize dynamic gallery/upload JPEGs in-place
    gallery_dirs = ['gallery', 'uploads', 'about boccia/category']
    for gdir in gallery_dirs:
        if os.path.exists(gdir):
            for root, dirs, files in os.walk(gdir):
                for file in files:
                    if file.lower().endswith(('.jpg', '.jpeg')):
                        full_path = os.path.join(root, file)
                        # Only optimize if larger than 150KB
                        if os.path.getsize(full_path) > 150 * 1024:
                            optimize_jpeg_inplace(full_path, max_width=1200, quality=75)

    # 3. Update references in code files
    update_references('.', image_map)
    print("Optimization Complete!")
