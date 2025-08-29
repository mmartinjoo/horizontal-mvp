import spacy
import json
from .services.fireworks import Fireworks

def extract_entities(text: str) -> dict:
    """
    Extracts People, Dates, and heuristically determined Topics from a given text.

    Args:
        text (str): The input text to process.

    Returns:
        dict: A dictionary containing lists of identified 'People', 'Dates', and 'Topics'.
    """
    
    nlp = spacy.load("en_core_web_sm")

    doc = nlp(text)

    people = []
    dates = []
    for ent in doc.ents:
        if ent.label_ == "PERSON":
            people.append(ent.text)
        elif ent.label_ == "DATE":
            dates.append(ent.text)

    return {
        "people": list(set(people)),
        "dates": list(set(dates)),
    }

def extract_keywords(text: str, fireworks: Fireworks) -> list:
    if len(text) == 0:
        return []
    
    prompt = f"""
        You are an expert product manager with 20+ years of experience in managing engineering teams nad products.
        
        You work at a company called Horizontal. We developer a knowledge management app for engineering teams.
        
        We integrate all the tools a team use, such as:
        - Google Drive
        - Slack
        - Jira
        - etc
        
        We collect all their work-related documents, conversations, and provide one unified interface to run smart and fast searches that understand their context.
        
        Users can ask questions like:
        - What did we discussed with John last week about choosing the right database for the project?
        
        The app would scan Slack conversations, Google Drive files, Jira tickets, etc and come up with an answer like:
        ```
            You decided to start the project with Postgres.
            
            It can handle vector embedding with pgvector and it has awesome search vector and full-text search features.
            
            The other option was OpenSearch but the team lacks the technical knowledge so you'll start with Postgres.
            
            Slack conversation: <link>
            Google Doc: <link>
        ```
        
        As you can see, we use their data to answer these questions.

        Your task is to perform deep analysis of the content of a document and return all relevant keywords.
        
        In order to run awesome searches, we need to understand the context of their document.
        
        So this is a CRUCIAL task.
        
        If a document contains text like this:
        ```
        AI-based job board
        
        
        MatchQ is an AI-powered job board where we match candidates with companies like Tinder.
        
        Here's the flow for a candidate:
        - A candidate registers
        - Uploads their CV
        - In 30-60 seconds, we're going to show them the top job listings. These listings are specific to the given applicant based on their profile and CV
        - He can then swipe the listings left or right
            
        Here's the flow for a company:
        - They register
        - Uploads their job listing
        - In 30-60 seconds, we're going to show them the top candidates. These candidates are specific to the given job role based on the profile, required skills, etc
        - They can then swipe the candidates left or right
            
        Once they match, they can message each other.
        ```
        
        You should extract keywords like:
        - AI-powered
        - job board
        - match
        - candidates
        - companies
        - swipe left/right
        - job listings
        - profile matching
        - MatchQ
        
        YOU MUST ALWAYS RESPOND IN THE FOLLOWING JSON FORMAT:
        ```
            [
                "AI-powered",
                "job board",
                "match",
                "candidated"
            ]
        ```
        
        Here's the content:
        {text}
    """
        
    json_str = fireworks.completion(prompt=prompt, max_tokens=1024)
    parsed = json.loads(json_str)
    if isinstance(parsed, list):
        return parsed
    else:
        raise json.JSONDecodeError("Keyword list cannot be decoded to JSON", json_str, 0)