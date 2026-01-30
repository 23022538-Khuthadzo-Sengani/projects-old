import networkx as nx
import pandas as pd
from typing import List, Dict

class UnivenAssistant:
    def __init__(self):
        # Campus Graph: Nodes are buildings/locations, edges are paths with distances (fictional but realistic, in meters)
        self.campus_graph = nx.Graph()

        # Add nodes (based on known Univen locations: residences, academic buildings, etc.)
        buildings = [
            "Admin Building", "Library", "Life Sciences Building", "D-Block Lecture Hall",
            "Bernard Ncube Residence", "Carousel Residence", "F3 Residence", "F4 Residence",
            "F5 Residence", "Lost City Boys Residence", "Lost City Girls Residence",
            "Mango Grove Residence", "Riverside Residence", "New Male Residence",
            "New Female Residence", "Mvelaphanda Male Residence", "Mvelaphanda Female Residence",
            "Sports Center", "Cafeteria", "Main Gate"
        ]
        for b in buildings:
            self.campus_graph.add_node(b)

        # Add edges with fictional distances in meters
        edges = [
            ("Admin Building", "Library", 200),
            ("Admin Building", "Cafeteria", 300),
            ("Library", "Life Sciences Building", 150),
            ("Life Sciences Building", "D-Block Lecture Hall", 250),
            ("D-Block Lecture Hall", "Sports Center", 400),
            ("Sports Center", "Cafeteria", 300),
            ("Library", "Bernard Ncube Residence", 500),
            ("Library", "Carousel Residence", 600),
            ("Carousel Residence", "F3 Residence", 100),
            ("F3 Residence", "F4 Residence", 80),
            ("F4 Residence", "F5 Residence", 90),
            ("F5 Residence", "Lost City Boys Residence", 150),
            ("Lost City Boys Residence", "Lost City Girls Residence", 100),
            ("Lost City Girls Residence", "Mango Grove Residence", 200),
            ("Riverside Residence", "New Male Residence", 300),
            ("New Male Residence", "New Female Residence", 120),
            ("New Female Residence", "Mvelaphanda Male Residence", 150),
            ("Mvelaphanda Male Residence", "Mvelaphanda Female Residence", 100),
            ("Mvelaphanda Female Residence", "Sports Center", 350),
            ("Main Gate", "Admin Building", 500),
            ("Main Gate", "Riverside Residence", 600),
        ]
        for u, v, d in edges:
            self.campus_graph.add_edge(u, v, weight=d)

        # Sample academic programs data
        data = {
            "Program": ["BSc Computer Science", "BCom Accounting", "BA Social Work", "B.Ed Education", "BSc Agriculture", "Bachelor of Nursing"],
            "Min NSC Points": [35, 30, 28, 32, 30, 40],
            "Required Subjects": ["Math, English", "Accounting, Math", "English, Life Orientation", "English, Math", "Math, Life Sciences", "English, Life Sciences, Math"],
            "Field": ["science", "commerce", "social sciences", "education", "science", "health"]
        }
        self.programs_df = pd.DataFrame(data)

        # Program weights for learning (start at 0.5)
        self.program_weights = {p: 0.5 for p in self.programs_df["Program"]}

    def find_optimal_path(self, start: str, end: str):
        # Find shortest path using Dijkstra's algorithm, case-insensitive.
        try:
            start_normalized = start.title()
            end_normalized = end.title()
            path = nx.shortest_path(self.campus_graph, start_normalized, end_normalized, weight="weight")
            distance = nx.shortest_path_length(self.campus_graph, start_normalized, end_normalized, weight="weight")
            return path, distance
        except nx.NetworkXNoPath:
            return None, None
        except nx.NodeNotFound:
            return None, None

    def suggest_programs(self, user_points: int, user_subjects: Dict[str, int], preferences: str) -> List[str]:
        # Suggest programs based on NSC points, subjects (subject:percentage), and field preference.
        import re
        def normalize_subject(s: str) -> str:
            return re.sub(r'[^a-z ]', '', s.lower()).strip()

        def subject_matches(req: str, user_subj: str, user_pct: int, min_level: int) -> bool:
            if user_pct < min_level:
                return False
            r = normalize_subject(req)
            u = normalize_subject(user_subj)
            if not r or not u:
                return False
            if r == u or r in u or u in r:
                return True
            if set(r.split()) & set(u.split()):
                return True
            return False

        matches = []
        # First pass: strict matching including preference
        for _, row in self.programs_df.iterrows():
            try:
                min_points = int(row.get("Min NSC Points", 0))
            except:
                min_points = 0
            if user_points < min_points:
                continue

            req_subjects = [s.strip() for s in str(row.get("Required Subjects", "")).split(",") if s.strip()]
            min_level = 50

            matches_all = True
            for req in req_subjects:
                found = False
                for user_subj, pct in user_subjects.items():
                    if subject_matches(req, user_subj, pct, min_level):
                        found = True
                        break
                if not found:
                    matches_all = False
                    break

            field_ok = (preferences.lower() in str(row.get("Field", "")).lower()) or preferences == ""

            if matches_all and field_ok:
                weighted_score = self.program_weights.get(row["Program"], 0.0)
                matches.append((row["Program"], weighted_score))

        # If nothing matched, relax criteria: ignore preference
        if not matches:
            for _, row in self.programs_df.iterrows():
                try:
                    min_points = int(row.get("Min NSC Points", 0))
                except:
                    min_points = 0
                if user_points < min_points:
                    continue
                req_subjects = [s.strip() for s in str(row.get("Required Subjects", "")).split(",") if s.strip()]
                min_level = 50
                matches_all = True
                for req in req_subjects:
                    found = False
                    for user_subj, pct in user_subjects.items():
                        if subject_matches(req, user_subj, pct, min_level):
                            found = True
                            break
                    if not found:
                        matches_all = False
                        break
                if matches_all:
                    weighted_score = self.program_weights.get(row["Program"], 0.0)
                    matches.append((row["Program"], weighted_score))

        # If still nothing, permissive: at least one subject matches
        if not matches:
            for _, row in self.programs_df.iterrows():
                try:
                    min_points = int(row.get("Min NSC Points", 0))
                except:
                    min_points = 0
                if user_points < min_points:
                    continue
                req_subjects = [s.strip() for s in str(row.get("Required Subjects", "")).split(",") if s.strip()]
                min_level = 50
                any_match = False
                for req in req_subjects:
                    for user_subj, pct in user_subjects.items():
                        if subject_matches(req, user_subj, pct, min_level):
                            any_match = True
                            break
                    if any_match:
                        break
                if any_match:
                    weighted_score = self.program_weights.get(row["Program"], 0.0)
                    matches.append((row["Program"], weighted_score))

        matches.sort(key=lambda x: x[1], reverse=True)
        return [prog for prog, _ in matches[:5]]

    def process_feedback(self, suggested_program: str, feedback: bool):
        # Improve: Adjust weight based on positive/negative feedback.
        if suggested_program in self.program_weights:
            if feedback:  # Positive
                self.program_weights[suggested_program] += 0.1
            else:  # Negative
                self.program_weights[suggested_program] -= 0.1 if self.program_weights[suggested_program] > 0.5 else 0

    def classify_inquiry(self, query: str) -> str:
        # Classify query using keywords.
        query_lower = query.lower()
        if any(word in query_lower for word in ["path", "route", "how to get", "from", "to", "campus map"]):
            return "navigation"
        elif any(word in query_lower for word in ["degree", "program", "suggest", "requirements", "nsc", "subjects"]):
            return "program_suggestion"
        else:
            return "other"


if __name__ == "__main__":
    assistant = UnivenAssistant()
    while True:
        query = input("Enter your inquiry (or 'exit' to quit): ")
        if query.lower() == "exit":
            break
        category = assistant.classify_inquiry(query)
        if category == "navigation":
            start = input("Enter starting location: ")
            end = input("Enter destination: ")
            path, dist = assistant.find_optimal_path(start, end)
            if path:
                print(f"Optimal path: {' -> '.join(path)} (Distance: {dist}m)")
            else:
                print("No path found.")
        elif category == "program_suggestion":
            points = int(input("Enter your NSC/APS points: "))
            subjects = {}
            num_subj = int(input("How many subjects? "))
            for _ in range(num_subj):
                subj = input("Subject name: ")
                perc = int(input("Percentage: "))
                subjects[subj] = perc
            pref = input("Preference (e.g., humanities, health): ")
            suggestions = assistant.suggest_programs(points, subjects, pref)
            print("Suggested programs:", suggestions)
            if suggestions:
                fb_prog = suggestions[0]
                fb = input("Was this helpful? (yes/no): ").lower() == "yes"
                assistant.process_feedback(fb_prog, fb)
                print("Feedback recorded. Assistant improved!")
        else:
            print("Sorry, I can only help with navigation or program suggestions for now.")