<?php
/**
 * Attend Ease - Sign-Up Examples
 *
 * Demonstrates how students and teachers register with sample data.
 *
 * @package AttendEase
 */

require_once __DIR__ . '/../config.php';

$pageTitle = 'Sign-Up Examples | ' . APP_NAME;
$basePath = '../';
include '../includes/header.php';
?>

<div class="examples-container">
    <div class="examples-header">
        <h1>Sign-Up Examples</h1>
        <p>See how students and teachers create their accounts on Attend Ease. Each role has slightly different required information.</p>
    </div>

    <div class="example-grid">
        <!-- Student Example -->
        <div class="example-card">
            <div class="example-header student">
                <span class="icon">&#127891;</span>
                <h2>Student Sign-Up</h2>
                <span class="badge">Student Role</span>
            </div>
            <div class="example-body">
                <div class="sample-form">
                    <div class="sample-field">
                        <label>Full Name *</label>
                        <div class="sample-input highlight">Maria Clara Santos</div>
                        <div class="field-note">Your complete name as it appears on school records. This is used for attendance reporting.</div>
                    </div>

                    <div class="sample-field">
                        <label>Student ID <span class="label-optional">(optional)</span></label>
                        <div class="sample-input highlight">2024-00042</div>
                        <div class="field-note">Your official student ID number. Helps teachers verify your identity during attendance checks.</div>
                    </div>

                    <div class="sample-field">
                        <label>Email *</label>
                        <div class="sample-input highlight">maria.santos@university.edu</div>
                        <div class="field-note">A valid email address. You'll use this (or your auto-generated username) to log in.</div>
                    </div>

                    <div class="sample-field">
                        <label>Password *</label>
                        <div class="sample-input">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</div>
                        <div class="field-note">Minimum 6 characters. A strong password includes uppercase, numbers, and symbols.</div>
                    </div>

                    <div class="sample-field">
                        <label>Confirm Password *</label>
                        <div class="sample-input">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</div>
                    </div>

                    <div class="sample-field">
                        <label>Role</label>
                        <div class="sample-input">&#127891; Student (selected)</div>
                    </div>

                    <div class="submit-demo student-demo">Create Student Account</div>
                </div>

                <div class="field-note field-note-success">
                    <strong>After signing up:</strong><br>
                    &rarr; Username auto-generated: <code>mariaclarasantos</code><br>
                    &rarr; Log in with username or email<br>
                    &rarr; Access student dashboard to scan QR codes and check in
                </div>
            </div>
        </div>

        <!-- Teacher Example -->
        <div class="example-card">
            <div class="example-header teacher">
                <span class="icon">&#127979;</span>
                <h2>Teacher Sign-Up</h2>
                <span class="badge">Teacher Role</span>
            </div>
            <div class="example-body">
                <div class="sample-form">
                    <div class="sample-field">
                        <label>Full Name *</label>
                        <div class="sample-input highlight">Prof. Juan Dela Cruz</div>
                        <div class="field-note">Your professional name. This appears on attendance reports and session records.</div>
                    </div>

                    <div class="sample-field">
                        <label>Subject You Teach *</label>
                        <div class="sample-input highlight">Computer Science</div>
                        <div class="field-note">Required for teachers. Helps students identify your sessions and classes.</div>
                    </div>

                    <div class="sample-field">
                        <label>Email *</label>
                        <div class="sample-input highlight">juan.cruz@university.edu</div>
                        <div class="field-note">Your institutional email. Used for login and system notifications.</div>
                    </div>

                    <div class="sample-field">
                        <label>Password *</label>
                        <div class="sample-input">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</div>
                        <div class="field-note">Minimum 6 characters. Use a strong, unique password for your account.</div>
                    </div>

                    <div class="sample-field">
                        <label>Confirm Password *</label>
                        <div class="sample-input">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</div>
                    </div>

                    <div class="sample-field">
                        <label>Role</label>
                        <div class="sample-input">&#127979; Teacher (selected)</div>
                    </div>

                    <div class="submit-demo teacher-demo">Create Teacher Account</div>
                </div>

                <div class="field-note field-note-info">
                    <strong>After signing up:</strong><br>
                    &rarr; Username auto-generated: <code>juancruz</code><br>
                    &rarr; Log in with username or email<br>
                    &rarr; Access admin dashboard to create sessions, view reports, and manage attendance
                </div>
            </div>
        </div>
    </div>

    <!-- Comparison Table -->
    <div class="comparison-table">
        <h3>&#128203; Field Comparison: Student vs Teacher</h3>
        <table>
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Student</th>
                    <th>Teacher</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Full Name</strong></td>
                    <td><span class="check">&#10003; Required</span></td>
                    <td><span class="check">&#10003; Required</span></td>
                    <td>Used for display and reports</td>
                </tr>
                <tr>
                    <td><strong>Email</strong></td>
                    <td><span class="check">&#10003; Required</span></td>
                    <td><span class="check">&#10003; Required</span></td>
                    <td>Must be unique; used for login</td>
                </tr>
                <tr>
                    <td><strong>Password</strong></td>
                    <td><span class="check">&#10003; Required (min 6)</span></td>
                    <td><span class="check">&#10003; Required (min 6)</span></td>
                    <td>Stored securely with bcrypt hashing</td>
                </tr>
                <tr>
                    <td><strong>Student ID</strong></td>
                    <td><span class="check">&#10003; Optional</span></td>
                    <td><span class="cross">&#10007; N/A</span></td>
                    <td>Helps verify student identity</td>
                </tr>
                <tr>
                    <td><strong>Subject</strong></td>
                    <td><span class="cross">&#10007; N/A</span></td>
                    <td><span class="check">&#10003; Required</span></td>
                    <td>Identifies teacher's specialization</td>
                </tr>
                <tr>
                    <td><strong>Username</strong></td>
                    <td colspan="2" class="centered"><span class="check">&#10003; Auto-generated</span></td>
                    <td>Derived from name or email prefix</td>
                </tr>
                <tr>
                    <td><strong>Role</strong></td>
                    <td><span class="check">&#10003; student</span></td>
                    <td><span class="check">&#10003; teacher</span></td>
                    <td>Determines dashboard and permissions</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Call to Action -->
    <div class="cta-section">
        <p>Ready to create your account? Choose your path below.</p>
        <div class="cta-buttons">
            <a href="<?php echo BASE_URL; ?>Registration/register.php" class="btn btn-admin">Go to Registration Form</a>
            <a href="<?php echo BASE_URL; ?>Registration/login.php" class="btn btn-slate">Already Registered? Log In</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

