def generate_recommendations(
    matching_skills: list[str],
    missing_skills: list[str],
) -> list[str]:
    """
    Generate actionable CV improvement recommendations.

    Matching skills:
        Skills found in both the CV and job description.

    Missing skills:
        Skills mentioned in the job description but not
        identified in the CV.
    """

    recommendations = []

    # Step 1: Recommend highlighting relevant existing skills.
    if matching_skills:
        skills = ", ".join(matching_skills)

        recommendations.append(
            f"Highlight your relevant skills ({skills}) in your CV. "
            "Where possible, include specific projects or achievements "
            "that demonstrate how you have used these technologies."
        )

    # Step 2: Generate recommendations for skills not evidenced in the CV.
    for skill in missing_skills:
        recommendations.append(
            f"The job description mentions {skill}, but this skill "
            "was not identified in your CV. If you have experience "
            f"with {skill}, include a specific example demonstrating it. "
            "Otherwise, consider developing this skill through "
            "training or practical projects."
        )

    # Step 3: Provide a general CV improvement recommendation.
    recommendations.append(
        "Use measurable achievements where possible. "
        "For example, describe the applications you developed, "
        "the technologies you used, and the results you achieved."
    )

    # Step 4: Recommend tailoring the CV to the job.
    recommendations.append(
        "Tailor your professional summary and experience descriptions "
        "to the job requirements, while keeping all information "
        "accurate and truthful."
    )

    return recommendations