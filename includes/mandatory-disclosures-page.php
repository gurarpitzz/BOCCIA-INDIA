<?php
// includes/mandatory-disclosures-page.php - Custom Template for MYAS Mandatory Disclosures
$page_title = "Mandatory Disclosures | Boccia India";
$meta_desc = "Official compliance details, disclosures, and certifications required under the National Sports Development Code of India.";
$canonical_url = "page.php?section=myas&slug=mandatory-disclosures";

include __DIR__ . '/header.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board%20bg.png');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- MYAS Disclosures --</span>
                <h1 class="board-hero-title">MANDATORY DISCLOSURES</h1>
                <p class="board-hero-text">
                    Compliance status under the National Sports Development Code of India 2011.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="board-section">
        <div class="container">
            
            <div class="row mb-4">
                <div class="col-md-8 mx-auto text-center">
                    <div class="section-title-wrapper mb-4">
                        <span class="sub-label">sports code compliance</span>
                        <h3 class="board-subtitle" style="color: #081B4B !important;">Compliance Matrix</h3>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="row mb-4">
                <div class="col-lg-6 mx-auto">
                    <div class="input-group shadow-sm rounded-pill overflow-hidden border">
                        <span class="input-group-text bg-white border-0 ps-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search text-muted" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                            </svg>
                        </span>
                        <input type="text" id="tableSearch" class="form-control border-0 py-3 pe-4" placeholder="Search compliance particulars or references..." style="font-size: 0.95rem; outline: none; box-shadow: none;">
                    </div>
                </div>
            </div>

            <!-- Disclosures Table Container -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow rounded-4 overflow-hidden bg-white">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="disclosuresTable" style="font-size: 0.95rem; min-width: 900px;">
                                <thead style="background: #081B4B; color: #ffffff;">
                                    <tr>
                                        <th style="width: 5%; padding: 1.25rem 1rem;" class="text-center">#</th>
                                        <th style="width: 50%; padding: 1.25rem 1rem;">Particulars</th>
                                        <th style="width: 20%; padding: 1.25rem 1rem;">Reference in Sports Code</th>
                                        <th style="width: 25%; padding: 1.25rem 1rem;">Position & Supporting Links</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Row 1 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">1</td>
                                        <td>
                                            Whether confirmation from Registrar of Societies/Registrar of Companies confirming that the body is still registered and is complying with the requirements of the Act under which it is registered has been furnished?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure II, para. 3.1.</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted mt-1">
                                                Latest certificate uploaded.
                                                <a href="uploads/documents/Certificate___List_of_governing_body.pdf" target="_blank" class="d-inline-flex align-items-center mt-1 text-decoration-none fw-semibold text-primary">
                                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg> View Certificate
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 2 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">2</td>
                                        <td>
                                            Were any amendments made to the Constitution?<br>
                                            If yes, have they been furnished to Registrar of Societies/Registrar of Companies?<br>
                                            Have copies thereof been furnished to the MYAS? Are they in compliance with the Sports Code?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure II, para. 3.2.</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted mt-1">
                                                Latest constitution registered.
                                                <a href="uploads/documents/Boccia_Sports_Federation_of_lndia_2026_affiliation.pdf" target="_blank" class="d-inline-flex align-items-center mt-1 text-decoration-none fw-semibold text-primary">
                                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg> View Constitution
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 3 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">3</td>
                                        <td>
                                            Have duly audited books of accounts been uploaded on the website? Is the NSF following the mercantile system of accounting?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure II, para. 3.7</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted mt-1">
                                                Mercantile system followed.
                                                <a href="uploads/documents/Certificate___List_of_governing_body.pdf" target="_blank" class="d-inline-flex align-items-center mt-1 text-decoration-none fw-semibold text-primary">
                                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg> Audited Accounts
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 4 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">4</td>
                                        <td>
                                            Did the NSF intimate the MYAS well in advance about its General Body Meeting and other Meetings where elections of office bearers and other important decisions are to be taken? Were observers sent to such meetings permitted to attend the meetings without hindrance? Are the reports of the observers on record? Are the reports in order?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure II, para. 3.13.</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted mt-1">
                                                General Body Meeting details.
                                                <a href="page.php?section=myas&slug=minutes-of-meetings" class="d-inline-flex align-items-center mt-1 text-decoration-none fw-semibold text-primary">
                                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg> View Minutes of GBM
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 5 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">5</td>
                                        <td>
                                            Is the NSF still registered with the International Federation concerned?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure II, para. 3.15.</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted mt-1">
                                                Affiliated with IPC / World Boccia.
                                                <a href="https://worldboccia.com" target="_blank" rel="noopener" class="d-inline-flex align-items-center mt-1 text-decoration-none fw-semibold text-primary">
                                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg> World Boccia Website
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 6 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">6</td>
                                        <td>
                                            Whether the NSF held a General Body Meeting at least once a year, as well as an appropriate meeting for the elections?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure II, para. 3.18.</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted mt-1">
                                                Held annually.
                                                <a href="page.php?section=myas&slug=minutes-of-meetings" class="d-inline-flex align-items-center mt-1 text-decoration-none fw-semibold text-primary">
                                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg> View Minutes
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 7 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">7</td>
                                        <td>
                                            Whether any legal action has been taken by the Registrar of Societies or other legal authority?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure III, para.1(iii).</td>
                                        <td>
                                            <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold">NO</span>
                                        </td>
                                    </tr>

                                    <!-- Row 8 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">8</td>
                                        <td>
                                            Whether sufficient documents have been furnished to demonstrate how government funds have been properly utilized.
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure III, para. 1(vi).</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold">YES</span>
                                        </td>
                                    </tr>

                                    <!-- Row 9 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">9</td>
                                        <td>
                                            Whether information as required by the Right to Information Act, 2005 and the Sports Code have been published on the website of the NSF?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure XV, Annexure XVIII</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold">YES</span>
                                        </td>
                                    </tr>

                                    <!-- Row 10 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">10</td>
                                        <td>
                                            If there has been any change in the EC, what is the tenure of all members of the EC? Are they in compliance with the tenure guidelines?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure II, para. 3.5.</td>
                                        <td>
                                            <div class="fw-semibold text-dark mb-1">No change in EC</div>
                                            <div style="font-size: 0.85rem;" class="text-muted">
                                                Tenure is compliant (max 4 years). Elected as per Hon'ble Delhi High Court interim order W.P. (C) 10647/2019.
                                                <a href="page.php?section=myas&slug=elections" class="d-inline-flex align-items-center mt-1 text-decoration-none fw-semibold text-primary">
                                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg> Election Compliance
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 11 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">11</td>
                                        <td>
                                            Have any of the persons in the EC been elected to any other NSF?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure II, para. 3.6.</td>
                                        <td>
                                            <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold">NO</span>
                                        </td>
                                    </tr>

                                    <!-- Row 12 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">12</td>
                                        <td>
                                            Whether the NSF has included prominent sportspersons of outstanding merit by election in its General Body and the Executive Committee?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure II, para. 3.20 &amp; 9.3(xii)</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted">
                                                Sportspersons details included in the Board of Directors list.
                                                <a href="page.php?section=about&slug=board" class="d-inline-flex align-items-center mt-1 text-decoration-none fw-semibold text-primary">
                                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg> View Board Members
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 13 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">13</td>
                                        <td>
                                            Has the NSF held annual National Championships for specified age-groups at the Senior, Junior and Sub-Junior levels for the past year? Have documents in support of the same been furnished with the Application?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure II, para. 3.8</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted">
                                                Championship logs updated.
                                                <a href="competitions/national-events.php" class="d-inline-flex align-items-center mt-1 text-decoration-none fw-semibold text-primary">
                                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg> View Championships
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 14 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">14</td>
                                        <td>
                                            Whether the Long Term Development Plan has been drawn up (as applicable)? If already drawn up, is the Plan being complied with? Have milestones set herein for the past year been met?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure X.</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold">YES</span>
                                        </td>
                                    </tr>

                                    <!-- Row 15 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">15</td>
                                        <td>
                                            <strong>1.</strong> Whether Advance Calendar has been drawn up for participation in competitions/training abroad and holding of international events in India?<br>
                                            <strong>2.</strong> Pl indicate changes made to the schedule and venues as published in the advance calendar for the last four years along with reasons.<br>
                                            <strong>3.</strong> Whether the Calendar has been furnished to the Government by 5th December?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure XIX.</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted">
                                                <strong>Changes:</strong> Athletics Nationals 2021 was postponed from Chennai to Bangalore due to COVID.
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 16 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">16</td>
                                        <td>
                                            <strong>1.</strong> Selection of coaches, selection of athletes etc in accordance with Guidelines?<br>
                                            <strong>2.</strong> Yearly calendar of coaching camp consonance with International/National events?<br>
                                            <strong>3.</strong> Furnished to the Govt and made available on website?<br>
                                            <strong>4.</strong> Any venue change without approval?<br>
                                            <strong>5.</strong> Travel plan and ticketing informed to each player in advance?<br>
                                            <strong>6.</strong> Coaches/scientists changed before Olympic/Asian Games over?<br>
                                            <strong>7.</strong> Any Coach or support staff has a tainted record?<br>
                                            <strong>8.</strong> Selection criteria norms communicated and uploaded on website?<br>
                                            <strong>9.</strong> Minimum qualifying norms fixed and announced?<br>
                                            <strong>10-12.</strong> Selection trials timelines compliant?<br>
                                            <strong>13.</strong> Selection Committee composition compliant?<br>
                                            <strong>14-15.</strong> Athletes selection changes compliant?<br>
                                            <strong>16.</strong> Athlete performances published and updated monthly?<br>
                                            <strong>17.</strong> Grievance redressal/Appeal mechanism in place?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure XXI.</td>
                                        <td>
                                            <div style="font-size: 0.85rem;" class="text-muted">
                                                <strong>Coaching/Camps:</strong> YES<br>
                                                <strong>Yearly Calendar:</strong> YES<br>
                                                <strong>Venue changes:</strong> NO<br>
                                                <strong>Ticketing informed:</strong> YES<br>
                                                <strong>Staff changed:</strong> NO<br>
                                                <strong>Tainted records:</strong> NO<br>
                                                <strong>Norms/Criteria:</strong> YES<br>
                                                <strong>Timelines:</strong> Compliant<br>
                                                <strong>Grievances:</strong> BSFI has instituted effective, transparent and fair grievance redressal systems for athletes.
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 17 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">17</td>
                                        <td>
                                            Are 2/3rds of the State/UTs of India affiliated with the NSF? Are they duly registered entities? Are they active entities?<br>
                                            Are the State/UT Associations each affiliating at least 50% of the District Associations? Are they registered and active?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure II, para 3.4, 3.10, 3.19.</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted">
                                                2/3rds of State Units affiliated.
                                                <a href="index.php#map" class="d-inline-flex align-items-center mt-1 text-decoration-none fw-semibold text-primary">
                                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg> View State Units Map
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 18 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">18</td>
                                        <td>
                                            Whether the NSF has complied with conditions laid down in the Code, including as to anti-doping, prevention of age fraud, citizenship criteria for selection of National Team, prevention of sexual harassment etc?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure II, para. 3.21.</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted">
                                                Compliant with General Code of Conduct (Points 20.1.1 to 20.10.4).
                                                <a href="page.php?section=sport&slug=anti-doping" class="d-inline-flex align-items-center mt-1 text-decoration-none fw-semibold text-primary">
                                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg> Anti-Doping Guidelines
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 19 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">19</td>
                                        <td>
                                            <strong>1.</strong> Whether measures against age fraud have been taken by the NSF?<br>
                                            <strong>2.</strong> Whether particulars of the identity cards issued have been uploaded on the website?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure XVI (25.9.2009)</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted">
                                                Information collected along with Aadhar &amp; Disability certificates, logged securely in the athlete registry.
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 20 -->
                                    <tr>
                                        <td class="text-center fw-bold text-muted">20</td>
                                        <td>
                                            <strong>1.</strong> Whether measures to prevent sexual harassment of women in sports have been taken by the NSF?<br>
                                            <strong>2.</strong> What is the present constitution of the Complaints Committee?
                                        </td>
                                        <td class="text-muted fw-semibold">Annexure XVI (12.8.2010)</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">YES</span>
                                            <div style="font-size: 0.85rem;" class="text-muted">
                                                Complaints committee and POSH compliance committee are duly constituted.
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var searchInput = document.getElementById("tableSearch");
    searchInput.addEventListener("keyup", function() {
        var value = this.value.toLowerCase();
        var rows = document.querySelectorAll("#disclosuresTable tbody tr");
        rows.forEach(function(row) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.indexOf(value) > -1 ? "" : "none";
        });
    });
});
</script>

<?php
include __DIR__ . '/footer.php';
?>
