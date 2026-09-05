/**
 * image-compressor.js
 * Client-side transparent image compression utility for registration forms.
 */

async function compressImageFile(
    file,
    {
        maxDimension = 1800,
        targetSize = 600 * 1024,
        minQuality = 0.55,
        initialQuality = 0.82
    } = {}
) {
    if (!file || !file.type || !file.type.startsWith("image/")) {
        return file;
    }

    // Don't waste time recompressing already-small images
    if (file.size <= 400 * 1024) {
        return file;
    }

    return new Promise((resolve) => {
        const img = new Image();
        const objectUrl = URL.createObjectURL(file);

        img.onload = async () => {
            URL.revokeObjectURL(objectUrl);

            let width = img.naturalWidth;
            let height = img.naturalHeight;

            if (!width || !height) {
                resolve(file);
                return;
            }

            // Preserve aspect ratio
            if (Math.max(width, height) > maxDimension) {
                const scale = maxDimension / Math.max(width, height);
                width = Math.round(width * scale);
                height = Math.round(height * scale);
            }

            const canvas = document.createElement("canvas");
            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext("2d", {
                alpha: false
            });

            if (!ctx) {
                resolve(file);
                return;
            }

            // White background for PNGs with transparency
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, width, height);

            ctx.drawImage(img, 0, 0, width, height);

            // Try progressively lower JPEG quality
            let quality = initialQuality;
            let blob = null;

            while (quality >= minQuality) {
                blob = await new Promise((resolveBlob) => {
                    canvas.toBlob(
                        resolveBlob,
                        "image/jpeg",
                        quality
                    );
                });

                if (!blob || blob.size <= targetSize) {
                    break;
                }

                quality -= 0.08;
            }

            // If compression failed or produced a larger result, retain original
            if (!blob || blob.size >= file.size) {
                resolve(file);
                return;
            }

            const compressedFile = new File(
                [blob],
                file.name.replace(/\.[^.]+$/, ".jpg"),
                {
                    type: "image/jpeg",
                    lastModified: file.lastModified || Date.now()
                }
            );

            resolve(compressedFile);
        };

        img.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            resolve(file);
        };

        img.src = objectUrl;
    });
}

async function prepareFile(file) {
    if (!file) return null;

    if (file.type && file.type.startsWith("image/")) {
        try {
            return await compressImageFile(file);
        } catch (e) {
            console.warn("Image compression error, falling back to original file:", e);
            return file;
        }
    }

    // PDFs and other documents remain untouched
    return file;
}

function calculateTotalPayloadSize(files) {
    let total = 0;
    for (const f of files) {
        if (f && f.size) {
            total += f.size;
        }
    }
    return total;
}
