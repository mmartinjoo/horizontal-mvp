import requests
import json
from requests.exceptions import HTTPError

class Fireworks:
    def __init__(self, api_key: str, model: str):
        self.api_key = api_key
        self.model = model
        self.base_url = 'https://api.fireworks.ai/inference/v1'
        
    def completion(self, prompt: str, max_tokens: int) -> str:
        headers = {
            'Authorization': f'Bearer {self.api_key}',
            'Content-Type': 'application/json',
        }

        payload = {
            'model': self.model,
            'prompt': prompt,
            'max_tokens': max_tokens,
        }
        
        try:
            response = requests.post(
                url=self.base_url+"/completions",
                headers=headers,
                json=payload,
                timeout=300
            )

            response.raise_for_status()
            res = response.json()

            if not res.get('choices') or not res['choices'][0].get('text'):
                raise Exception(f'Fireworks: No completion found: {json.dumps(res)}')

            return self.sanitizeJSON(res['choices'][0]['text'])
        except HTTPError as http_err:
            raise Exception(f'HTTP error occurred: {http_err} - Response: {response.text}') from http_err
        except requests.exceptions.Timeout as timeout_err:
            raise Exception(f'Request timed out: {timeout_err}') from timeout_err
        except requests.exceptions.RequestException as req_err:
            raise Exception(f'An error occurred during the request: {req_err}') from req_err
        except Exception as e:
            raise e
        
    def sanitizeJSON(self, json: str) -> str:
        # Trim leading/trailing whitespace
        cleaned_string = json.strip()
        cleaned_string = cleaned_string.replace("```json", "")
        cleaned_string = cleaned_string.replace("```", "")
        
        return cleaned_string