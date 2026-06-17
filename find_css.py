import shutil

src = r"C:\Users\HP\.gemini\antigravity-browser-profile\Default\Cache\Cache_Data\f_00237c"
dst = r"c:\Users\HP\.gemini\antigravity-ide\scratch\boccia-india-landing\styles.css"

print("Copying cached CSS to styles.css...")
shutil.copy(src, dst)
print("Restored styles.css from cache!")
