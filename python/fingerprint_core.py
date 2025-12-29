import json
import sys
import hashlib
import random
import time

class FingerprintCore:
    def __init__(self):
        self.minutiae_points = []
        
    def capture_fingerprint(self, sensor_type="windows_hello"):
        """Capture fingerprint from available sensor"""
        try:
            if sensor_type == "windows_hello":
                return self._capture_windows_hello()
            elif sensor_type == "optical":
                return self._capture_optical_sensor()
            else:
                return self._simulate_capture()
                
        except Exception as e:
            return {"status": "error", "message": str(e)}
    
    def _capture_windows_hello(self):
        """Capture using Windows Hello fingerprint sensor"""
        try:
            # Simulate Windows Hello API call
            # In real implementation, use Windows Biometric Framework
            time.sleep(2)  # Simulate capture time
            
            # Generate simulated minutiae points
            minutiae = self._generate_minutiae_template()
            
            return {
                "status": "success",
                "template": minutiae,
                "sensor_type": "Windows Hello",
                "quality_score": random.uniform(85, 98)
            }
            
        except Exception as e:
            return {"status": "error", "message": f"Windows Hello error: {str(e)}"}
    
    def _capture_optical_sensor(self):
        """Capture using optical fingerprint sensor"""
        try:
            # Simulate optical sensor capture
            time.sleep(1.5)
            
            minutiae = self._generate_minutiae_template()
            
            return {
                "status": "success",
                "template": minutiae,
                "sensor_type": "Optical Sensor",
                "quality_score": random.uniform(80, 95)
            }
            
        except Exception as e:
            return {"status": "error", "message": f"Optical sensor error: {str(e)}"}
    
    def _simulate_capture(self):
        """Simulate fingerprint capture for testing"""
        time.sleep(1)
        
        minutiae = self._generate_minutiae_template()
        
        return {
            "status": "success",
            "template": minutiae,
            "sensor_type": "Simulated",
            "quality_score": random.uniform(75, 90)
        }
    
    def _generate_minutiae_template(self):
        """Generate minutiae points template"""
        # Simulate minutiae extraction
        num_points = random.randint(20, 40)
        minutiae_points = []
        
        for i in range(num_points):
            point = {
                "x": random.randint(0, 255),
                "y": random.randint(0, 255),
                "angle": random.randint(0, 359),
                "type": random.choice(["ridge_ending", "bifurcation"]),
                "quality": random.uniform(0.7, 1.0)
            }
            minutiae_points.append(point)
        
        # Create hash-based template
        template_data = json.dumps(minutiae_points, sort_keys=True)
        template_hash = hashlib.sha256(template_data.encode()).hexdigest()
        
        return {
            "minutiae": minutiae_points,
            "hash": template_hash,
            "point_count": len(minutiae_points)
        }
    
    def match_fingerprints(self, live_template, stored_template, threshold=0.75):
        """Match two fingerprint templates"""
        try:
            if isinstance(live_template, str):
                live_template = json.loads(live_template)
            if isinstance(stored_template, str):
                stored_template = json.loads(stored_template)
            
            # Extract minutiae points
            live_points = live_template.get("minutiae", [])
            stored_points = stored_template.get("minutiae", [])
            
            if not live_points or not stored_points:
                return {"status": "error", "message": "Invalid template data"}
            
            # Calculate matching score
            match_score = self._calculate_match_score(live_points, stored_points)
            is_match = match_score >= threshold
            
            return {
                "status": "success",
                "is_match": is_match,
                "match_score": round(match_score, 3),
                "live_points": len(live_points),
                "stored_points": len(stored_points)
            }
            
        except Exception as e:
            return {"status": "error", "message": str(e)}
    
    def _calculate_match_score(self, live_points, stored_points):
        """Calculate fingerprint matching score"""
        if not live_points or not stored_points:
            return 0.0
        
        matches = 0
        tolerance = 10  # Pixel tolerance for matching
        
        for live_point in live_points:
            for stored_point in stored_points:
                # Check if points are close enough
                x_diff = abs(live_point["x"] - stored_point["x"])
                y_diff = abs(live_point["y"] - stored_point["y"])
                angle_diff = abs(live_point["angle"] - stored_point["angle"])
                
                if (x_diff <= tolerance and 
                    y_diff <= tolerance and 
                    angle_diff <= 30 and
                    live_point["type"] == stored_point["type"]):
                    matches += 1
                    break
        
        # Calculate match percentage
        max_points = max(len(live_points), len(stored_points))
        match_score = matches / max_points if max_points > 0 else 0
        
        return match_score
    
    def assess_quality(self, template):
        """Assess fingerprint template quality"""
        try:
            if isinstance(template, str):
                template = json.loads(template)
            
            minutiae = template.get("minutiae", [])
            point_count = len(minutiae)
            
            # Quality factors
            count_score = min(1.0, point_count / 25)  # Ideal: 25+ points
            
            # Average quality of individual points
            if minutiae:
                avg_quality = sum(p.get("quality", 0.5) for p in minutiae) / len(minutiae)
            else:
                avg_quality = 0
            
            # Distribution score (points spread across image)
            distribution_score = self._calculate_distribution_score(minutiae)
            
            overall_quality = (count_score * 0.4 + avg_quality * 0.4 + distribution_score * 0.2) * 100
            
            return {
                "status": "success",
                "quality_score": round(overall_quality, 2),
                "point_count": point_count,
                "distribution": round(distribution_score * 100, 2)
            }
            
        except Exception as e:
            return {"status": "error", "message": str(e)}
    
    def _calculate_distribution_score(self, minutiae):
        """Calculate how well distributed the minutiae points are"""
        if len(minutiae) < 4:
            return 0.5
        
        # Divide image into quadrants and check distribution
        quadrants = [0, 0, 0, 0]  # TL, TR, BL, BR
        
        for point in minutiae:
            x, y = point["x"], point["y"]
            if x < 128 and y < 128:
                quadrants[0] += 1  # Top-left
            elif x >= 128 and y < 128:
                quadrants[1] += 1  # Top-right
            elif x < 128 and y >= 128:
                quadrants[2] += 1  # Bottom-left
            else:
                quadrants[3] += 1  # Bottom-right
        
        # Good distribution has points in all quadrants
        non_empty_quadrants = sum(1 for q in quadrants if q > 0)
        return non_empty_quadrants / 4.0

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"status": "error", "message": "No command specified"}))
        return
    
    command = sys.argv[1]
    fp_core = FingerprintCore()
    
    if command == "capture":
        sensor_type = sys.argv[2] if len(sys.argv) > 2 else "simulate"
        result = fp_core.capture_fingerprint(sensor_type)
        print(json.dumps(result))
        
    elif command == "match":
        if len(sys.argv) < 4:
            print(json.dumps({"status": "error", "message": "Missing templates"}))
            return
        
        live_template = sys.argv[2]
        stored_template = sys.argv[3]
        threshold = float(sys.argv[4]) if len(sys.argv) > 4 else 0.75
        
        result = fp_core.match_fingerprints(live_template, stored_template, threshold)
        print(json.dumps(result))
        
    elif command == "quality":
        if len(sys.argv) < 3:
            print(json.dumps({"status": "error", "message": "Missing template"}))
            return
        
        template = sys.argv[2]
        result = fp_core.assess_quality(template)
        print(json.dumps(result))
        
    else:
        print(json.dumps({"status": "error", "message": "Unknown command"}))

if __name__ == "__main__":
    main()