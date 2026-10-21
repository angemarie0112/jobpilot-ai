from gemini_service import generate_cover_letter


# Step 1: Define a sample CV.
cv_text = """
Name: Test User

Skills:
Python, Laravel, Vue.js, PostgreSQL

Education:
Computer Science

Experience:
Web Development and Machine Learning
"""


# Step 2: Define a sample job description.
job_description = """
We are looking for a Junior AI Engineer with experience
in Python, Machine Learning, PostgreSQL, and Docker.

The ideal candidate should have knowledge of REST APIs,
backend development, and database management.

Experience with FastAPI, Laravel, and Vue.js is
considered an advantage.
"""


# Step 3: Generate the cover letter.
print("Generating cover letter with Gemini...\n")

result = generate_cover_letter(
    cv_text=cv_text,
    job_description=job_description,
    job_title="Junior AI Engineer",
    company_name="Tech Innovations",
)


# Step 4: Display the result.
print("=" * 60)
print("TITLE:")
print(result["title"])

print("\nMODEL:")
print(result["model_used"])

print("\nGENERATED COVER LETTER:")
print("=" * 60)
print(result["content"])
print("=" * 60)