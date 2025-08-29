import os
from flask import Flask, request, jsonify
from src.extractor import extract_entities, extract_keywords
from src.services.fireworks import Fireworks
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)

@app.route('/extract', methods=['POST'])
def extract():
    """
    API endpoint to extract entities (People, Dates, Topics) from text.
    Expects a JSON payload with a 'text' field.
    """
    if not request.is_json:
        return jsonify({"error": "Request must be JSON"}), 400

    data = request.get_json()
    text = data.get('text')

    if not text:
        return jsonify({"error": "Missing 'text' field in request body"}), 400

    try:
        fireworks = Fireworks(
            api_key=os.getenv('FIREWORKS_API_KEY'),
            model=os.getenv('FIREWORKS_MODEL'),
        )
        results = extract_entities(text)
        try:
            keywords = extract_keywords(text, fireworks)
        except Exception as e:
            app.logger.error(f"Unable to extract keywords: {e}", exc_info=True)
            keywords = []
            
        print("KEYWORDS")
        print(keywords)
            
        return jsonify({
            "people": results["people"],
            "dates": results["dates"],
            "keywords": keywords,
        }), 200
    except Exception as e:
        app.logger.error(f"Error processing request: {e}", exc_info=True)
        return jsonify({"error": "An internal server error occurred", "details": str(e)}), 500

@app.route('/', methods=['GET'])
def home():
    return "NER API is running. Send a POST request to /extract with your text."

if __name__ == '__main__':
    app.run(debug=True)
