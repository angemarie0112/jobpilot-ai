<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $tailoredCv->title }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 38px 45px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            line-height: 1.55;
            color: #334155;
        }

        .header {
            text-align: center;
            padding-bottom: 14px;
            border-bottom: 2px solid #4f46e5;
            margin-bottom: 20px;
        }

        .name {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .contact {
            font-size: 9px;
            color: #475569;
            line-height: 1.7;
            overflow-wrap: break-word;
        }

        .section {
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #312e81;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 5px;
            margin-bottom: 9px;
            page-break-after: avoid;
        }

        .section-start {
            page-break-inside: avoid;
        }

        .summary {
            text-align: left;
            line-height: 1.6;
            white-space: pre-line;
        }

        .skills {
            line-height: 1.7;
        }

        .entry {
            margin-bottom: 13px;
        }

        .entry-heading {
            page-break-inside: avoid;
            page-break-after: avoid;
        }

        .entry-title {
            font-size: 10.5px;
            font-weight: bold;
            color: #0f172a;
        }

        .entry-subtitle {
            font-size: 9.5px;
            color: #475569;
            margin-top: 2px;
        }

        .entry-date {
            color: #64748b;
            font-size: 9px;
            margin-top: 2px;
        }

        ul {
            margin-top: 5px;
            margin-bottom: 0;
            padding-left: 18px;
        }

        li {
            margin-bottom: 4px;
            line-height: 1.55;
        }

        .project-description {
            margin-top: 4px;
        }

        .technologies {
            font-size: 9px;
            color: #475569;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    @php
        $content = $tailoredCv->content ?? [];
        $contact = $content['contact'] ?? [];

        $contactDetails = array_filter([
            $contact['email'] ?? null,
            $contact['phone'] ?? null,
            $contact['location'] ?? null,
            $contact['linkedin'] ?? null,
            $contact['github'] ?? null,
        ]);

        $experienceItems = $content['experience'] ?? [];
        $educationItems = $content['education'] ?? [];
        $projectItems = $content['projects'] ?? [];
    @endphp

    <!-- Contact -->
    <div class="header">
        <div class="name">
            {{ $contact['full_name'] ?? 'Candidate' }}
        </div>

        @if(count($contactDetails))
            <div class="contact">
                {{ implode(' | ', $contactDetails) }}
            </div>
        @endif
    </div>

    <!-- Professional Summary -->
    @if(!empty($content['professional_summary']))
        <div class="section">
            <div class="section-start">
                <div class="section-title">Professional Summary</div>
            </div>

            <div class="summary">
                {{ $content['professional_summary'] }}
            </div>
        </div>
    @endif

    <!-- Technical Skills -->
    @if(!empty($content['skills']))
        <div class="section">
            <div class="section-start">
                <div class="section-title">Technical Skills</div>

                <div class="skills">
                    {{ implode(' • ', $content['skills']) }}
                </div>
            </div>
        </div>
    @endif

    <!-- Professional Experience -->
    @if(!empty($experienceItems))
        <div class="section">

            @foreach($experienceItems as $experience)
                @if($loop->first)
                    <div class="section-start">
                        <div class="section-title">
                            Professional Experience
                        </div>

                        <div class="entry-heading">
                            <div class="entry-title">
                                {{ $experience['job_title'] ?? '' }}
                            </div>

                            @if(!empty($experience['company']) || !empty($experience['location']))
                                <div class="entry-subtitle">
                                    {{ implode(' | ', array_filter([
                                        $experience['company'] ?? null,
                                        $experience['location'] ?? null,
                                    ])) }}
                                </div>
                            @endif

                            @if(!empty($experience['start_date']) || !empty($experience['end_date']))
                                <div class="entry-date">
                                    {{ implode(' – ', array_filter([
                                        $experience['start_date'] ?? null,
                                        $experience['end_date'] ?? null,
                                    ])) }}
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="entry-heading">
                        <div class="entry-title">
                            {{ $experience['job_title'] ?? '' }}
                        </div>

                        @if(!empty($experience['company']) || !empty($experience['location']))
                            <div class="entry-subtitle">
                                {{ implode(' | ', array_filter([
                                    $experience['company'] ?? null,
                                    $experience['location'] ?? null,
                                ])) }}
                            </div>
                        @endif

                        @if(!empty($experience['start_date']) || !empty($experience['end_date']))
                            <div class="entry-date">
                                {{ implode(' – ', array_filter([
                                    $experience['start_date'] ?? null,
                                    $experience['end_date'] ?? null,
                                ])) }}
                            </div>
                        @endif
                    </div>
                @endif

                <div class="entry">
                    @if(!empty($experience['responsibilities']))
                        <ul>
                            @foreach($experience['responsibilities'] as $responsibility)
                                <li>{{ $responsibility }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

        </div>
    @endif

    <!-- Education -->
    @if(!empty($educationItems))
        <div class="section">

            @foreach($educationItems as $education)
                <div class="entry">

                    @if($loop->first)
                        <div class="section-title">
                            Education
                        </div>
                    @endif

                    <div class="entry-heading">
                        <div class="entry-title">
                            {{ $education['degree'] ?? '' }}
                        </div>

                        @if(!empty($education['institution']) || !empty($education['location']))
                            <div class="entry-subtitle">
                                {{ implode(' | ', array_filter([
                                    $education['institution'] ?? null,
                                    $education['location'] ?? null,
                                ])) }}
                            </div>
                        @endif

                        @if(!empty($education['start_date']) || !empty($education['end_date']))
                            <div class="entry-date">
                                {{ implode(' – ', array_filter([
                                    $education['start_date'] ?? null,
                                    $education['end_date'] ?? null,
                                ])) }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

        </div>
    @endif

    <!-- Projects -->
    @if(!empty($projectItems))
        <div class="section">

            @foreach($projectItems as $project)
                <div class="entry">

                    @if($loop->first)
                        <div class="section-title">
                            Projects
                        </div>
                    @endif

                    <div class="entry-heading">
                        <div class="entry-title">
                            {{ $project['name'] ?? '' }}
                        </div>
                    </div>

                    @if(!empty($project['description']))
                        <div class="project-description">
                            {{ $project['description'] }}
                        </div>
                    @endif

                    @if(!empty($project['technologies']))
                        <div class="technologies">
                            Technologies:
                            {{ implode(', ', $project['technologies']) }}
                        </div>
                    @endif
                </div>
            @endforeach

        </div>
    @endif

    <!-- Certifications -->
    @if(!empty($content['certifications']))
        <div class="section">
            <div class="section-title">
                Certifications
            </div>

            <ul>
                @foreach($content['certifications'] as $certification)
                    <li>{{ $certification }}</li>
                @endforeach
            </ul>
        </div>
    @endif

</body>
</html>