import os

from dotenv import load_dotenv
from google import genai

# Step 1: Load environment variables from .env
load_dotenv()

# Step 2: Retrieve the Gemini API key
api_key = os.getenv("GEMINI_API_KEY")

if not api_key:
    raise ValueError(
        "GEMINI_API_KEY is missing. Please check your .env file."
    )

# Step 3: Create the Gemini client
client = genai.Client(api_key=api_key)

# Step 4: Select the model
model_name = os.getenv("GEMINI_MODEL", "gemini-3.8-flash")

# Step 5: Send a simple test request
print("Connecting to Gemini...")

response = client.models.generate_content(
    model=model_name,
    contents="Say hello and confirm that the JobPilot AI connection is working."
)

# Step 6: Display the response
print("\nGemini response:")
print(response.text)