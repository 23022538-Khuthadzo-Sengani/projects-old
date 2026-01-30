from flask import Flask, render_template, request, jsonify
import chatbot_module as mod

app = Flask(__name__)

# Instantiate assistant
assistant = None
if hasattr(mod, "UnivenAssistant"):
    try:
        assistant = mod.UnivenAssistant()
    except Exception as e:
        print("Error initializing UnivenAssistant:", e)

@app.route('/')
def home():
    return render_template('index.html')

@app.route('/get', methods=['POST'])
def get_bot_response():
    data = request.get_json(force=True)
    message = data.get('message', '').strip()
    if not message:
        return jsonify(response="Please send a message.")

    low = message.lower().strip()

    # greetings
    if low in ['hi','hello','hey']:
        return jsonify(response='Welcome to Univen Campus Assistant!')

    # classify
    category = 'other'
    try:
        if assistant and hasattr(assistant, 'classify_inquiry'):
            category = assistant.classify_inquiry(message)
    except Exception as e:
        print("classify error:", e)
        category = 'other'

    if category == 'navigation' or any(w in low for w in ['how do i get','how to get','route','from','to','campus map']):
        # try from X to Y pattern
        import re
        m = re.search(r'from\s+(.+?)\s+to\s+(.+)', message, re.I)
        if m:
            import re as _re
            start = _re.sub(r'[^\w\s]', '', m.group(1).strip()).title()
            end = _re.sub(r'[^\w\s]', '', m.group(2).strip()).title()
            try:
                result = assistant.find_optimal_path(start, end)
                # handle (path, distance) or single list
                path = None
                dist = None
                if isinstance(result, tuple) and len(result) >= 2:
                    path, dist = result[0], result[1]
                elif isinstance(result, list):
                    path = result
                if path:
                    if dist is not None:
                        return jsonify(response=f"Best path from {start} to {end}: {' -> '.join(path)} (approx {dist} m)")
                    else:
                        return jsonify(response=f"Best path from {start} to {end}: {' -> '.join(path)}")
                else:
                    return jsonify(response=f"Sorry, couldn't find a path between {start} and {end}.")
            except Exception as e:
                return jsonify(response=f"Error finding path: {e}")
        else:
            return jsonify(response='Please ask like: "How do I get from Admin Building to Library?"')

    # program suggestion
    if category == 'program_suggestion' or any(w in low for w in ['program','suggest','degree','requirements','nsc','subjects','points']):
        # parse points and subjects
        import re
        m = re.search(r'(\d{1,3})\s*(?:points|pt|p)\b', message, re.I)
        pts = int(m.group(1)) if m else 0
        subjects = {}
        # remove the matched points phrase from the message before extracting subject:score pairs
        msg_for_subjects = message
        if m:
            msg_for_subjects = message[:m.start()] + " " + message[m.end():]
        for m in re.finditer(r'([A-Za-z][A-Za-z ]{0,29})[:\-\s]+(\d{1,3})', msg_for_subjects):
            subj = m.group(1).strip().title()
            try:
                val = int(m.group(2))
                subjects[subj] = val
            except:
                pass
        prefs = ''
        for pref in ['health','humanities','commerce','engineering','management','science','social']:
            if pref in low:
                prefs = pref
                break
        if assistant and hasattr(assistant, 'suggest_programs'):
            try:
                suggestions = assistant.suggest_programs(pts, subjects, prefs)
                if suggestions:
                    return jsonify(response='Here are suggested programs based on your input: ' + ', '.join(suggestions))
                else:
                    return jsonify(response="I couldn't find matching programs with the provided info. Try: 'Suggest programs with 60 points, Mathematics 70, Physical Science 65, preference: health'")
            except Exception as e:
                return jsonify(response=f"Error generating program suggestions: {e}")
        else:
            return jsonify(response='Program suggestion feature is unavailable.')

    # feedback
    import re
    fb_match = re.search(r'feedback\s+([A-Za-z0-9\s]+)\s+(yes|no|true|false|y|n)', low)
    if fb_match and assistant and hasattr(assistant, 'process_feedback'):
        prog = fb_match.group(1).strip().title()
        fb_str = fb_match.group(2)
        fb_bool = fb_str in ['yes','true','y']
        try:
            assistant.process_feedback(prog, fb_bool)
            return jsonify(response=f"Thanks — recorded feedback for {prog}: {'positive' if fb_bool else 'negative'}")
        except Exception as e:
            return jsonify(response=f"Error recording feedback: {e}")

    caps = ("I can help with navigation (e.g., 'How do I get from Admin Building to Library?'), \n"
            "suggest programs if you provide NSC points and subject percentages (e.g., 'Suggest programs with 60 points, Math 70, Science 65'), \n"
            "and record feedback (e.g., 'feedback <program name> yes'). What would you like to do?")
    return jsonify(response=caps)

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)
