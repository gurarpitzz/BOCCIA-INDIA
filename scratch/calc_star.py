import math

def generate_rounded_star(outer_r, inner_r, num_points=5, corner_radius=3):
    points = []
    # Generate standard star points
    for i in range(num_points * 2):
        angle = i * math.pi / num_points - math.pi / 2
        r = outer_r if i % 2 == 0 else inner_r
        x = 50 + r * math.cos(angle)
        y = 50 + r * math.sin(angle)
        points.append((x, y))
    
    # Generate rounded path
    path = []
    for i in range(num_points * 2):
        curr = points[i]
        prev = points[(i - 1) % (num_points * 2)]
        nxt = points[(i + 1) % (num_points * 2)]
        
        # Calculate direction vectors
        v1 = (prev[0] - curr[0], prev[1] - curr[1])
        v2 = (nxt[0] - curr[0], nxt[1] - curr[1])
        
        # Normalize
        l1 = math.hypot(*v1)
        l2 = math.hypot(*v2)
        u1 = (v1[0]/l1, v1[1]/l1)
        u2 = (v2[0]/l2, v2[1]/l2)
        
        # Corner control points
        p1 = (curr[0] + u1[0] * corner_radius, curr[1] + u1[1] * corner_radius)
        p2 = (curr[0] + u2[0] * corner_radius, curr[1] + u2[1] * corner_radius)
        
        if i == 0:
            path.append(f"M {p1[0]:.2f},{p1[1]:.2f}")
        else:
            path.append(f"L {p1[0]:.2f},{p1[1]:.2f}")
        path.append(f"Q {curr[0]:.2f},{curr[1]:.2f} {p2[0]:.2f},{p2[1]:.2f}")
        
    path.append("Z")
    return " ".join(path)

# Let's generate a star that fits in 100x100 box
# Outer radius 46 (fits within 4 to 96)
# Inner radius 22
# Corner radius 5
star_path = generate_rounded_star(45, 20, 5, 6)
print(star_path)
