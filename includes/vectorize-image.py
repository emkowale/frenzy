#!/usr/bin/env python3
"""
File: includes/vectorize-image.py
Description: Reduces a raster image to N colors and returns PNG path + hex color list (JSON output)
Plugin: Frenzy
Author: Eric Kowalewski
Last Updated: 2025-08-06 03:25 EDT
"""

import sys
import os
import json
from PIL import Image
import numpy as np
import cv2

def reduce_colors(image_path, color_count):
    image = Image.open(image_path).convert('RGB')
    image = image.resize((800, 800))  # Optional: scale to simplify color reduction

    np_image = np.array(image)
    data = np_image.reshape((-1, 3)).astype(np.float32)

    criteria = (cv2.TERM_CRITERIA_EPS + cv2.TERM_CRITERIA_MAX_ITER, 10, 1.0)
    _, labels, centers = cv2.kmeans(data, color_count, None, criteria, 10, cv2.KMEANS_RANDOM_CENTERS)

    reduced = centers[labels.flatten()].reshape(np_image.shape).astype(np.uint8)
    reduced_image = Image.fromarray(reduced)

    output_path = image_path.replace(".png", "-simplified.png").replace(".jpg", "-simplified.png").replace(".jpeg", "-simplified.png")
    reduced_image.save(output_path)

    hex_colors = []
    for rgb in centers:
        r, g, b = [int(c) for c in rgb]
        hex_color = '#{:02x}{:02x}{:02x}'.format(r, g, b)
        if hex_color not in hex_colors:
            hex_colors.append(hex_color)

    return {
        'path': output_path,
        'colors': hex_colors
    }

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({'error': 'Usage: python3 vectorize-image.py <input_image> <color_count>'}))
        sys.exit(1)

    input_image = sys.argv[1]
    color_count = int(sys.argv[2])

    if not os.path.isfile(input_image):
        print(json.dumps({'error': 'File not found'}))
        sys.exit(1)

    try:
        result = reduce_colors(input_image, color_count)
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({'error': str(e)}))
        sys.exit(1)
