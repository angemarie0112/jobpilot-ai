import re


# ==================================================
# Supported Skills
# ==================================================

SKILLS = {

    # ----------------------------------------------
    # Software Development and IT
    # ----------------------------------------------

    "Python": ["python"],
    "Laravel": ["laravel"],
    "Vue.js": ["vue.js", "vuejs", "vue js"],
    "PostgreSQL": ["postgresql", "postgres"],
    "Machine Learning": ["machine learning"],
    "Docker": ["docker"],
    "FastAPI": ["fastapi"],
    "REST APIs": [
        "rest api",
        "rest apis",
        "restful api",
        "restful apis",
    ],
    "JavaScript": ["javascript"],
    "TypeScript": ["typescript"],
    "PHP": ["php"],
    "SQL": ["sql"],
    "React": ["react", "react.js", "reactjs"],
    "Django": ["django"],
    "Git": ["git"],

    "Java": ["java"],
    "C++": ["c++"],
    "C#": ["c#"],
    "Node.js": ["node.js", "nodejs"],
    "HTML": ["html"],
    "CSS": ["css"],
    "Tailwind CSS": ["tailwind", "tailwind css"],
    "Bootstrap": ["bootstrap"],
    "MySQL": ["mysql"],
    "MongoDB": ["mongodb"],
    "Redis": ["redis"],
    "AWS": ["aws", "amazon web services"],
    "Azure": ["azure", "microsoft azure"],
    "Google Cloud": ["google cloud", "gcp"],
    "Kubernetes": ["kubernetes"],
    "CI/CD": ["ci/cd", "continuous integration"],
    "Linux": ["linux"],
    "GitHub": ["github"],

    # ----------------------------------------------
    # AI and Data Science
    # ----------------------------------------------

    "Artificial Intelligence": [
        "artificial intelligence",
    ],
    "Deep Learning": ["deep learning"],
    "Natural Language Processing": [
        "natural language processing",
        "nlp",
    ],
    "Computer Vision": ["computer vision"],
    "Data Analysis": [
        "data analysis",
        "data analytics",
    ],
    "Data Visualization": [
        "data visualization",
        "data visualisation",
    ],
    "Data Engineering": ["data engineering"],
    "Data Modeling": [
        "data modeling",
        "data modelling",
    ],
    "Statistics": ["statistics", "statistical analysis"],
    "TensorFlow": ["tensorflow"],
    "PyTorch": ["pytorch"],
    "Scikit-learn": ["scikit-learn", "sklearn"],
    "Pandas": ["pandas"],
    "NumPy": ["numpy"],
    "Power BI": ["power bi"],
    "Tableau": ["tableau"],
    "Excel": ["excel", "microsoft excel"],

    # ----------------------------------------------
    # Warehouse and Logistics
    # ----------------------------------------------

    "Inventory Management": [
        "inventory management",
        "inventory control",
        "inventory tracking",
    ],
    "Stock Control": [
        "stock control",
        "stock management",
    ],
    "Order Picking": [
        "order picking",
        "picking orders",
        "pick and pack",
    ],
    "Packing": [
        "packing",
        "packaging",
        "packing orders",
    ],
    "Shipping": [
        "shipping",
        "dispatching",
        "dispatch",
    ],
    "Receiving Goods": [
        "receiving goods",
        "goods receiving",
        "receiving deliveries",
    ],
    "Forklift Operation": [
        "forklift",
        "forklift operation",
        "forklift driving",
    ],
    "Warehouse Safety": [
        "warehouse safety",
        "workplace safety",
        "safety procedures",
    ],
    "Warehouse Management": [
        "warehouse management",
        "warehouse operations",
    ],
    "Logistics": [
        "logistics",
        "logistics operations",
    ],
    "Supply Chain Management": [
        "supply chain",
        "supply chain management",
    ],
    "Quality Control": [
        "quality control",
        "quality inspection",
    ],

    # ----------------------------------------------
    # Administration and Office
    # ----------------------------------------------

    "Data Entry": ["data entry"],
    "Document Management": [
        "document management",
        "document filing",
    ],
    "Office Administration": [
        "office administration",
        "administrative support",
    ],
    "Scheduling": [
        "scheduling",
        "appointment scheduling",
    ],
    "Record Keeping": [
        "record keeping",
        "record management",
    ],
    "Microsoft Office": [
        "microsoft office",
        "ms office",
    ],
    "Microsoft Word": [
        "microsoft word",
        "ms word",
    ],
    "Microsoft PowerPoint": [
        "powerpoint",
        "microsoft powerpoint",
    ],
    "Email Management": [
        "email management",
        "managing emails",
    ],

    # ----------------------------------------------
    # Customer Service and Sales
    # ----------------------------------------------

    "Customer Service": [
        "customer service",
        "customer support",
    ],
    "Customer Relationship Management": [
        "customer relationship management",
        "crm",
    ],
    "Sales": [
        "sales",
        "selling",
    ],
    "Negotiation": ["negotiation", "negotiating"],
    "Complaint Handling": [
        "complaint handling",
        "handling complaints",
    ],
    "Cash Handling": [
        "cash handling",
        "cash register",
    ],
    "Point of Sale": [
        "point of sale",
        "pos system",
        "pos systems",
    ],

    # ----------------------------------------------
    # Marketing
    # ----------------------------------------------

    "Digital Marketing": ["digital marketing"],
    "Social Media Marketing": [
        "social media marketing",
        "social media management",
    ],
    "SEO": [
        "seo",
        "search engine optimization",
        "search engine optimisation",
    ],
    "Content Creation": [
        "content creation",
        "content development",
    ],
    "Email Marketing": ["email marketing"],
    "Market Research": ["market research"],
    "Google Analytics": ["google analytics"],
    "Advertising": [
        "advertising",
        "advertisement campaigns",
    ],

    # ----------------------------------------------
    # Finance and Accounting
    # ----------------------------------------------

    "Accounting": ["accounting"],
    "Bookkeeping": ["bookkeeping"],
    "Financial Reporting": ["financial reporting"],
    "Budgeting": ["budgeting", "budget management"],
    "Invoicing": ["invoicing", "invoice processing"],
    "Payroll": ["payroll", "payroll processing"],
    "Financial Analysis": ["financial analysis"],
    "Tax Preparation": ["tax preparation"],

    # ----------------------------------------------
    # Healthcare
    # ----------------------------------------------

    "Patient Care": ["patient care"],
    "Patient Monitoring": ["patient monitoring"],
    "Medical Records": [
        "medical records",
        "electronic health records",
        "ehr",
    ],
    "First Aid": ["first aid"],
    "Infection Control": ["infection control"],
    "Medication Administration": [
        "medication administration",
    ],
    "Vital Signs": [
        "vital signs",
        "vital sign monitoring",
    ],

    # ----------------------------------------------
    # Hospitality
    # ----------------------------------------------

    "Food Preparation": [
        "food preparation",
        "food prep",
    ],
    "Food Safety": [
        "food safety",
        "food hygiene",
    ],
    "Housekeeping": ["housekeeping"],
    "Cleaning": [
        "cleaning",
        "cleaning procedures",
    ],
    "Front Desk": [
        "front desk",
        "reception duties",
    ],
    "Reservation Management": [
        "reservation management",
        "booking management",
    ],

    # ----------------------------------------------
    # General Professional Skills
    # ----------------------------------------------

    "Communication": [
        "communication skills",
        "verbal communication",
        "written communication",
    ],
    "Teamwork": [
        "teamwork",
        "team collaboration",
        "working in a team",
    ],
    "Problem Solving": [
        "problem solving",
        "problem-solving",
    ],
    "Time Management": ["time management"],
    "Leadership": [
        "leadership",
        "team leadership",
    ],
    "Project Management": ["project management"],
    "Attention to Detail": [
        "attention to detail",
        "detail oriented",
        "detail-oriented",
    ],
    "Organization": [
        "organizational skills",
        "organisational skills",
    ],
}


# ==================================================
# Extract Skills
# ==================================================

def extract_skills(text: str) -> list[str]:
    """
    Extract skills explicitly mentioned in text.

    Matching is case-insensitive and respects word
    boundaries to reduce false positives.
    """

    found_skills = []

    for skill, aliases in SKILLS.items():
        for alias in aliases:

            pattern = (
                r"(?<!\w)"
                + re.escape(alias)
                + r"(?!\w)"
            )

            if re.search(pattern, text, flags=re.IGNORECASE):
                found_skills.append(skill)
                break

    return found_skills


# ==================================================
# Compare Skills
# ==================================================

def compare_skills(
    cv_text: str,
    job_description: str,
) -> dict:
    """
    Compare explicitly recognized CV skills
    against recognized job requirements.
    """

    cv_skills = extract_skills(cv_text)
    job_skills = extract_skills(job_description)

    cv_skill_set = set(cv_skills)

    matching_skills = [
        skill
        for skill in job_skills
        if skill in cv_skill_set
    ]

    missing_skills = [
        skill
        for skill in job_skills
        if skill not in cv_skill_set
    ]

    return {
        "cv_skills": cv_skills,
        "job_skills": job_skills,
        "matching_skills": matching_skills,
        "missing_skills": missing_skills,
    }


# ==================================================
# Calculate Weighted Match Score
# ==================================================

def calculate_match_score(
    required_skills: list[str],
    preferred_skills: list[str],
    cv_skills: list[str],
) -> int:
    """
    Calculate a weighted match score.

    Required skills: 80%
    Preferred skills: 20%

    This is a heuristic skill match score,
    not a prediction of hiring success.
    """

    cv_skill_set = set(cv_skills)

    required_skills = list(dict.fromkeys(required_skills))

    preferred_skills = [
        skill
        for skill in dict.fromkeys(preferred_skills)
        if skill not in required_skills
    ]

    if not required_skills and not preferred_skills:
        raise ValueError(
            "No job skills available for scoring."
        )

    required_matches = sum(
        skill in cv_skill_set
        for skill in required_skills
    )

    preferred_matches = sum(
        skill in cv_skill_set
        for skill in preferred_skills
    )

    if required_skills and preferred_skills:
        required_weight = 0.8
        preferred_weight = 0.2

    elif required_skills:
        required_weight = 1.0
        preferred_weight = 0.0

    else:
        required_weight = 0.0
        preferred_weight = 1.0

    required_score = (
        required_matches / len(required_skills)
        if required_skills else 0
    )

    preferred_score = (
        preferred_matches / len(preferred_skills)
        if preferred_skills else 0
    )

    total_score = (
        required_score * required_weight
        + preferred_score * preferred_weight
    ) * 100

    return round(total_score)


# ==================================================
# Classify Required and Preferred Skills
# ==================================================

def classify_job_skills(job_description: str) -> dict:
    """
    Classify job skills as required or preferred.

    Supports:
    - Section headings
    - Bullet points
    - Paragraph-based descriptions

    This is a rule-based heuristic.
    """

    required_skills = []
    preferred_skills = []

    required_headings = {
        "requirements",
        "required",
        "required skills",
        "must have",
        "must-have",
        "essential skills",
        "qualifications",
    }

    preferred_headings = {
        "preferred",
        "preferred skills",
        "nice to have",
        "nice-to-have",
        "bonus skills",
        "desirable skills",
        "additional skills",
    }

    preferred_phrases = [
        "an advantage",
        "a plus",
        "nice to have",
        "nice-to-have",
        "preferred",
        "desirable",
        "bonus",
        "would be beneficial",
    ]

    required_phrases = [
        "required",
        "must have",
        "must-have",
        "essential",
        "should have",
        "looking for",
        "need to have",
    ]

    current_section = "required"

    for line in job_description.splitlines():

        cleaned_line = line.strip()

        if not cleaned_line:
            continue

        normalized_line = cleaned_line.lower().rstrip(":")

        if normalized_line in required_headings:
            current_section = "required"
            continue

        if normalized_line in preferred_headings:
            current_section = "preferred"
            continue

        sentences = re.split(
            r"(?<=[.!?])\s+(?=[A-Z])",
            cleaned_line
        )

        for sentence in sentences:

            sentence_lower = sentence.lower()
            skills = extract_skills(sentence)

            if not skills:
                continue

            if any(
                phrase in sentence_lower
                for phrase in preferred_phrases
            ):
                category = "preferred"

            elif any(
                phrase in sentence_lower
                for phrase in required_phrases
            ):
                category = "required"

            else:
                category = current_section

            if category == "preferred":
                for skill in skills:
                    if skill not in preferred_skills:
                        preferred_skills.append(skill)

            else:
                for skill in skills:
                    if skill not in required_skills:
                        required_skills.append(skill)

    preferred_skills = [
        skill
        for skill in preferred_skills
        if skill not in required_skills
    ]

    return {
        "required_skills": required_skills,
        "preferred_skills": preferred_skills,
    }