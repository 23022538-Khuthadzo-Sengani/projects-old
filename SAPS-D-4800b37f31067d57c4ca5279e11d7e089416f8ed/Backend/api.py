
from flask import Flask, request, jsonify
from flask_cors import CORS
import cv2
import numpy as np
import sqlite3
import os
import base64
import subprocess
import sys
from pathlib import Path

app = Flask(__name__)
CORS(app)  


base_dir = Path("C:\\Projects\\Docker\\Backend")
dataset_dir = base_dir / "dataset"
db_path = base_dir / "images.db"
face_cascade_path = base_dir / "haarcascade_frontalface_default.xml"

# Create directories if they don't exist
dataset_dir.mkdir(parents=True, exist_ok=True)

# Add the project directory to Python path
sys.path.append(str(base_dir))

# Import functions from your modules
try:
    from dataset_creater import insertorupdate, process_and_save_face
    from trainer import train_model
    from detect import get_user, recognize_face
except ImportError as e:
    print(f"Import error: {e}")

    
    def insertorupdate(Badge_ID, Name, Rank):
        conn = sqlite3.connect(str(db_path))
        cursor = conn.execute("SELECT * FROM EMPLOYEES WHERE Badge_ID=?", (Badge_ID,))
        isRecordExist = 0
        for row in cursor:
            isRecordExist = 1
        if isRecordExist == 1:
            conn.execute("UPDATE EMPLOYEES SET Name=?, Rank=? WHERE Badge_ID=?", (Name, Rank, Badge_ID))
        else:
            conn.execute("INSERT INTO EMPLOYEES (Badge_ID, Name, Rank) VALUES (?,?,?)", (Badge_ID, Name, Rank))
        conn.commit()
        conn.close()
        return True

    def process_and_save_face(image_data, badge_id):
        try:
            
            image_data = image_data.split(',')[1]  # Remove data:image/jpeg;base64,
            nparr = np.frombuffer(base64.b64decode(image_data), np.uint8)
            img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
            
            
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            
            
            faceDetect = cv2.CascadeClassifier(str(face_cascade_path))
            faces = faceDetect.detectMultiScale(gray, 1.3, 5)
            
            if len(faces) == 0:
                return {'success': False, 'message': 'No face detected'}
            
            # Count existing images for this user
            existing_images = list(dataset_dir.glob(f"user.{badge_id}.*.jpg"))
            sample_num = len(existing_images) + 1
            
            # Save the first face found
            x, y, w, h = faces[0]
            filename = dataset_dir / f"user.{badge_id}.{sample_num}.jpg"
            cv2.imwrite(str(filename), gray[y:y+h, x:x+w])
            
            return {
                'success': True, 
                'count': sample_num,
                'message': f'Face captured successfully ({sample_num}/20)'
            }
        
        except Exception as e:
            return {'success': False, 'message': str(e)}

    def train_model():
        try:
            result = subprocess.run(['python', 'trainer.py'], capture_output=True, text=True)
            if result.returncode == 0:
                return {"success": True, "message": "Model trained successfully"}
            else:
                return {"success": False, "message": result.stderr}
        except Exception as e:
            return {"success": False, "message": str(e)}

    def get_user(Badge_ID):
        conn = sqlite3.connect(str(db_path))
        cursor = conn.execute("SELECT * FROM EMPLOYEES WHERE Badge_ID=?", (Badge_ID,))
        user = None
        for row in cursor:
            user = row
        conn.close()
        return user

    def recognize_face(image_data=None):
        try:
            
            image_data = image_data.split(',')[1]  
            nparr = np.frombuffer(base64.b64decode(image_data), np.uint8)
            img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
            
            
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            
            
            faceDetect = cv2.CascadeClassifier(str(face_cascade_path))
            faces = faceDetect.detectMultiScale(gray, 1.3, 5)
            
            if len(faces) == 0:
                return {"success": False, "message": "No face detected"}
            
            
            recognizer = cv2.face.LBPHFaceRecognizer_create()
            recognizer_path = base_dir / 'recognizer/data.yml'
            
            if not recognizer_path.exists():
                return {"success": False, "message": "Model not trained yet"}
                
            recognizer.read(str(recognizer_path))
            
            # Predict the face
            x, y, w, h = faces[0]
            badge_id, conf = recognizer.predict(gray[y:y+h, x:x+w])
            
            
            profile = get_user(str(badge_id))
            
            if profile:
                return {
                    "success": True,
                    "verified": True,
                    "user": {
                        "badge_id": profile[0],
                        "name": profile[1],
                        "rank": profile[2]
                    },
                    "confidence": float(conf)
                }
            else:
                return {
                    "success": True,
                    "verified": False,
                    "message": "User not recognized"
                }
        
        except Exception as e:
            return {"success": False, "message": str(e)}

# Database initialization function
def init_db():
    conn = sqlite3.connect(str(db_path))
    conn.execute('''
        CREATE TABLE IF NOT EXISTS EMPLOYEES (
            Badge_ID INTEGER PRIMARY KEY,
            Name TEXT NOT NULL,
            Rank TEXT NOT NULL
        )
    ''')
    conn.commit()
    conn.close()

# Initialize database
init_db()

@app.route('/register', methods=['POST'])
def register_user():
    try:
        data = request.get_json()
        
        # Validate input
        if not data or 'badge_id' not in data or 'name' not in data or 'rank' not in data:
            return jsonify({'success': False, 'message': 'Missing required fields'}), 400
        
        badge_id = data['badge_id']
        name = data['name']
        rank = data['rank']
        
        # Validate badge ID
        if not badge_id.isdigit() or len(badge_id) != 5 or badge_id == '00000':
            return jsonify({'success': False, 'message': 'Invalid badge ID. Must be a 5-digit code that is not all zeros.'}), 400
        
        
        existing_user = get_user(badge_id)
        if existing_user:
            return jsonify({'success': False, 'message': 'User with this badge ID already exists'}), 400
        
        # Register user using the imported function
        success = insertorupdate(badge_id, name, rank)
        if not success:
            return jsonify({'success': False, 'message': 'Failed to register user in database'}), 500
        
        return jsonify({
            'success': True, 
            'message': 'User registered successfully',
            'data': {'badge_id': badge_id, 'name': name, 'rank': rank}
        })
    
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/capture_face', methods=['POST'])
def capture_face():
    try:
        data = request.get_json()
        
        if not data or 'badge_id' not in data or 'image' not in data:
            return jsonify({'success': False, 'message': 'Missing required fields'}), 400
        
        badge_id = data['badge_id']
        image_data = data['image']
        
        
        result = process_and_save_face(image_data, badge_id)
        
        if result['success']:
            return jsonify(result)
        else:
            return jsonify({'success': False, 'message': result['message']}), 400
    
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/train', methods=['GET'])
def train_model_endpoint():
    try:
        
        result = train_model()
        
        if result['success']:
            return jsonify({'success': True, 'message': result['message']})
        else:
            return jsonify({'success': False, 'message': result['message']}), 500
    
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/verify', methods=['POST'])
def verify_user():
    try:
        data = request.get_json()
        
        if not data or 'image' not in data:
            return jsonify({'success': False, 'message': 'Missing image data'}), 400
        
        image_data = data['image']
        
        
        result = recognize_face(image_data=image_data)
        
        if result['success']:
            return jsonify(result)
        else:
            return jsonify({'success': False, 'message': result['message']}), 400
    
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/realtime', methods=['GET'])
def realtime_recognition():
    try:
        
        return jsonify({
            'success': True,
            'message': 'Realtime recognition is available through the standalone detect.py script'
        })
    
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True, port=5000)