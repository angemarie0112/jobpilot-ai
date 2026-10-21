from fastapi import FastAPI, UploadFile, File, HTTPException
from pydantic import BaseModel, Field
import pymupdf
from gemini_service import generate_cover_letter
from cv_tailoring_service import tailor_cv

from matching import (
    compare_skills,
    classify_job_skills,
    calculate_match_score,
)
from recommendations import generate_recommendations

app = FastAPI(
    title="JobPilot AI Service",
    description="Python service for CV processing and AI-powered job matching.",
    version="1.0.0"
)


# --------------------------------------------------
# Request Models
# --------------------------------------------------

class MatchAnalysisRequest(BaseModel):
    cv_text: str = Field(
        min_length=1,
        max_length=100000
    )

    job_description: str = Field(
        min_length=1,
        max_length=50000
    )

class CoverLetterRequest(BaseModel):
    cv_text: str = Field(min_length=1, max_length=100000)
    job_description: str = Field(min_length=1, max_length=50000)
    job_title: str = Field(min_length=1, max_length=255)
    company_name: str = Field(min_length=1, max_length=255)


class TailorCvRequest(BaseModel):
    cv_text: str = Field(min_length=1, max_length=100000)
    job_description: str = Field(min_length=1, max_length=50000)
    job_title: str = Field(min_length=1, max_length=255)
    company_name: str = Field(min_length=1, max_length=255)
    match_analysis: dict = Field(default_factory=dict)

# --------------------------------------------------
# Health Check
# --------------------------------------------------

@app.get("/health")
def health_check():
    return {
        "status": "ok",
        "service": "JobPilot AI Service"
    }


# --------------------------------------------------
# PDF Text Extraction
# --------------------------------------------------

@app.post("/extract-text")
async def extract_text(file: UploadFile = File(...)):

    if not file.filename or not file.filename.lower().endswith(".pdf"):
        raise HTTPException(
            status_code=400,
            detail="Only PDF files are allowed."
        )

    max_file_size = 10 * 1024 * 1024

    try:
        pdf_bytes = await file.read(max_file_size + 1)

        if len(pdf_bytes) > max_file_size:
            raise HTTPException(
                status_code=413,
                detail="The PDF file must not exceed 10 MB."
            )

        try:
            with pymupdf.open(
                stream=pdf_bytes,
                filetype="pdf"
            ) as document:

                extracted_pages = []

                for page in document:
                    page_text = page.get_text("text")
                    extracted_pages.append(page_text)

                extracted_text = "\n".join(
                    extracted_pages
                ).strip()

                return {
                    "filename": file.filename,
                    "page_count": len(document),
                    "extracted_text": extracted_text
                }

        except (
            pymupdf.FileDataError,
            pymupdf.EmptyFileError
        ):
            raise HTTPException(
                status_code=422,
                detail="The uploaded file is not a valid readable PDF."
            )

    finally:
        await file.close()


# --------------------------------------------------
# CV and Job Description Matching
# --------------------------------------------------
@app.post("/analyze-match")
def analyze_match(request: MatchAnalysisRequest):

    # Step 1: Compare CV skills against job requirements.
    result = compare_skills(
        cv_text=request.cv_text,
        job_description=request.job_description,
    )

    # Step 2: Identify required and preferred job skills.
    classified_skills = classify_job_skills(
        request.job_description
    )

    required_skills = classified_skills["required_skills"]
    preferred_skills = classified_skills["preferred_skills"]

    # Step 3: Calculate the weighted match score.
    if required_skills or preferred_skills:
        match_score = calculate_match_score(
            required_skills=required_skills,
            preferred_skills=preferred_skills,
            cv_skills=result["cv_skills"],
        )
    else:
        match_score = None

    # Step 4: Generate CV improvement recommendations.
    recommendations = generate_recommendations(
        matching_skills=result["matching_skills"],
        missing_skills=result["missing_skills"],
    )

    # Step 5: Return the complete analysis.
    return {
        "message": "CV matching analysis completed successfully.",
        "status": "completed",
        "match_score": match_score,
        "required_skills": required_skills,
        "preferred_skills": preferred_skills,
        "recommendations": recommendations,
        **result,
    }


@app.post("/generate-cover-letter")
def create_cover_letter(request: CoverLetterRequest):
    """
    Generate a personalized cover letter using Gemini.
    """

    try:
        result = generate_cover_letter(
            cv_text=request.cv_text,
            job_description=request.job_description,
            job_title=request.job_title,
            company_name=request.company_name,
        )

        return {
            "message": "Cover letter generated successfully.",
            "status": "completed",
            **result,
        }

    except Exception:
        # Log the underlying error in the FastAPI terminal.
        import traceback
        traceback.print_exc()

        # Keep internal error details hidden from API clients.
        raise HTTPException(
            status_code=502,
            detail="The AI service could not generate the cover letter."
        )
# --------------------------------------------------
# AI CV Tailoring
# --------------------------------------------------
@app.post("/tailor-cv")
def create_tailored_cv(request: TailorCvRequest):
    """
    Generate a structured, job-tailored CV using Gemini.
    """

    try:
        result = tailor_cv(
            cv_text=request.cv_text,
            job_description=request.job_description,
            job_title=request.job_title,
            company_name=request.company_name,
            match_analysis=request.match_analysis,
        )

        return {
            "message": "CV tailored successfully.",
            "status": "completed",
            **result,
        }

    except Exception as exc:
        # Temporary debugging: print the underlying error
        # in the FastAPI terminal.
        import traceback
        traceback.print_exc()

        raise HTTPException(
            status_code=502,
            detail="The AI service could not generate the tailored CV."
        )