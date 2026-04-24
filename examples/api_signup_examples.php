<?php
/**
 * Attend Ease - API Sign-Up Examples
 *
 * Demonstrates how to register students and teachers programmatically
 * via HTTP POST requests using cURL, JavaScript fetch, and PHP.
 *
 * @package AttendEase
 */

require_once __DIR__ . '/../config.php';

$pageTitle = 'API Sign-Up Examples | ' . APP_NAME;
$basePath = '../';
include '../includes/header.php';
?>

<div class="api-container">
    <div class="api-header">
        <h1>API Sign-Up Examples</h1>
        <p>Register students and teachers programmatically via HTTP POST requests. Useful for bulk imports, integrations, and third-party apps.</p>
    </div>

    <!-- Endpoint Info -->
    <div class="api-section">
        <h2><span class="method">POST</span> Registration Endpoint</h2>
        <div class="content">
            <div class="endpoint-box">
                <span class="method-tag">POST</span>
                <code><?php echo BASE_URL; ?>Registration/register.php</code>
            </div>
            <p class="text-slate-no-margin">
                Submit a standard <code>application/x-www-form-urlencoded</code> POST request to the registration endpoint.
                The response is an HTML page with success or error messages. To detect success, check for a redirect
                or parse the response for <code>.alert-success</code>.
            </p>
        </div>
    </div>

    <!-- Parameters -->
    <div class="api-section">
        <h2>&#128221; Request Parameters</h2>
        <div class="content content-no-padding">
            <table class="param-table">
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>csrf_token</code></td>
                        <td>string</td>
                        <td><span class="required">Yes</span></td>
                        <td>CSRF token from session. Must be fetched from the form page first.</td>
                    </tr>
                    <tr>
                        <td><code>role</code></td>
                        <td>string</td>
                        <td><span class="required">Yes</span></td>
                        <td><code>student</code> or <code>teacher</code></td>
                    </tr>
                    <tr>
                        <td><code>full_name</code></td>
                        <td>string</td>
                        <td><span class="required">Yes</span></td>
                        <td>User's full name</td>
                    </tr>
                    <tr>
                        <td><code>email</code></td>
                        <td>string</td>
                        <td><span class="required">Yes</span></td>
                        <td>Valid email address (must be unique)</td>
                    </tr>
                    <tr>
                        <td><code>password</code></td>
                        <td>string</td>
                        <td><span class="required">Yes</span></td>
                        <td>Min. 6 characters</td>
                    </tr>
                    <tr>
                        <td><code>confirm_password</code></td>
                        <td>string</td>
                        <td><span class="required">Yes</span></td>
                        <td>Must match <code>password</code></td>
                    </tr>
                    <tr>
                        <td><code>student_id</code></td>
                        <td>string</td>
                        <td><span class="optional">Optional</span></td>
                        <td>Only for <code>role=student</code></td>
                    </tr>
                    <tr>
                        <td><code>subject</code></td>
                        <td>string</td>
                        <td><span class="required">Yes (teacher)</span></td>
                        <td>Only for <code>role=teacher</code> — subject taught</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- cURL Example -->
    <div class="api-section">
        <h2><span class="method">cURL</span> Example — Register a Student</h2>
        <div class="content">
<div class="code-block"><span class="comment"># Step 1: Get CSRF token from the registration page</span>
<span class="variable">CSRF</span>=$(curl -s -c cookies.txt <span class="string">"<?php echo BASE_URL; ?>Registration/register.php"</span> | grep -oP <span class="string">'value="\K[^"]+'</span> | head -1)

<span class="comment"># Step 2: Submit registration</span>
curl -X POST -b cookies.txt -c cookies.txt <span class="string">"<?php echo BASE_URL; ?>Registration/register.php"</span> \
  -H <span class="string">"Content-Type: application/x-www-form-urlencoded"</span> \
  -d <span class="string">"csrf_token=<span class="variable">$CSRF</span>"</span> \
  -d <span class="string">"role=student"</span> \
  -d <span class="string">"full_name=Maria+Clara+Santos"</span> \
  -d <span class="string">"student_id=2024-00042"</span> \
  -d <span class="string">"email=maria.santos%40university.edu"</span> \
  -d <span class="string">"password=StudentPass123%21"</span> \
  -d <span class="string">"confirm_password=StudentPass123%21"</span></div>
        </div>
    </div>

    <div class="api-section">
        <h2><span class="method">cURL</span> Example — Register a Teacher</h2>
        <div class="content">
<div class="code-block"><span class="comment"># Step 1: Get CSRF token from the registration page</span>
<span class="variable">CSRF</span>=$(curl -s -c cookies.txt <span class="string">"<?php echo BASE_URL; ?>Registration/register.php"</span> | grep -oP <span class="string">'value="\K[^"]+'</span> | head -1)

<span class="comment"># Step 2: Submit registration</span>
curl -X POST -b cookies.txt -c cookies.txt <span class="string">"<?php echo BASE_URL; ?>Registration/register.php"</span> \
  -H <span class="string">"Content-Type: application/x-www-form-urlencoded"</span> \
  -d <span class="string">"csrf_token=<span class="variable">$CSRF</span>"</span> \
  -d <span class="string">"role=teacher"</span> \
  -d <span class="string">"full_name=Prof.+Juan+Dela+Cruz"</span> \
  -d <span class="string">"subject=Computer+Science"</span> \
  -d <span class="string">"email=juan.cruz%40university.edu"</span> \
  -d <span class="string">"password=TeacherPass456%21"</span> \
  -d <span class="string">"confirm_password=TeacherPass456%21"</span></div>
        </div>
    </div>

    <!-- JavaScript fetch Example -->
    <div class="api-section">
        <h2><span class="method">JavaScript</span> fetch() Example</h2>
        <div class="content">
<div class="code-block"><span class="keyword">async function</span> <span class="function">registerUser</span>(userData) {
  <span class="comment">// Step 1: Fetch the registration page to get CSRF token</span>
  <span class="keyword">const</span> <span class="variable">csrfResponse</span> = <span class="keyword">await</span> <span class="function">fetch</span>(<span class="string">'<?php echo BASE_URL; ?>Registration/register.php'</span>);
  <span class="keyword">const</span> <span class="variable">csrfHtml</span> = <span class="keyword">await</span> <span class="variable">csrfResponse</span>.text();
  <span class="keyword">const</span> <span class="variable">csrfMatch</span> = <span class="variable">csrfHtml</span>.match(<span class="string">/value="([a-f0-9]{64})"/</span>);
  <span class="keyword">const</span> <span class="variable">csrfToken</span> = <span class="variable">csrfMatch</span> ? <span class="variable">csrfMatch</span>[1] : <span class="string">''</span>;

  <span class="comment">// Step 2: Build form data</span>
  <span class="keyword">const</span> <span class="variable">formData</span> = <span class="keyword">new</span> URLSearchParams();
  <span class="variable">formData</span>.append(<span class="string">'csrf_token'</span>, <span class="variable">csrfToken</span>);
  <span class="variable">formData</span>.append(<span class="string">'role'</span>, <span class="variable">userData</span>.role);
  <span class="variable">formData</span>.append(<span class="string">'full_name'</span>, <span class="variable">userData</span>.fullName);
  <span class="variable">formData</span>.append(<span class="string">'email'</span>, <span class="variable">userData</span>.email);
  <span class="variable">formData</span>.append(<span class="string">'password'</span>, <span class="variable">userData</span>.password);
  <span class="variable">formData</span>.append(<span class="string">'confirm_password'</span>, <span class="variable">userData</span>.password);

  <span class="keyword">if</span> (<span class="variable">userData</span>.role === <span class="string">'student'</span>) {
    <span class="variable">formData</span>.append(<span class="string">'student_id'</span>, <span class="variable">userData</span>.studentId);
  } <span class="keyword">else if</span> (<span class="variable">userData</span>.role === <span class="string">'teacher'</span>) {
    <span class="variable">formData</span>.append(<span class="string">'subject'</span>, <span class="variable">userData</span>.subject);
  }

  <span class="comment">// Step 3: Submit registration</span>
  <span class="keyword">const</span> <span class="variable">response</span> = <span class="keyword">await</span> <span class="function">fetch</span>(<span class="string">'<?php echo BASE_URL; ?>Registration/register.php'</span>, {
    method: <span class="string">'POST'</span>,
    headers: { <span class="string">'Content-Type'</span>: <span class="string">'application/x-www-form-urlencoded'</span> },
    body: <span class="variable">formData</span>,
    credentials: <span class="string">'include'</span>
  });

  <span class="keyword">const</span> <span class="variable">html</span> = <span class="keyword">await</span> <span class="variable">response</span>.text();
  <span class="keyword">return</span> <span class="variable">html</span>.includes(<span class="string">'alert-success'</span>);
}

<span class="comment">// Register a student</span>
<span class="function">registerUser</span>({
  role: <span class="string">'student'</span>,
  fullName: <span class="string">'Maria Clara Santos'</span>,
  studentId: <span class="string">'2024-00042'</span>,
  email: <span class="string">'maria.santos@university.edu'</span>,
  password: <span class="string">'StudentPass123!'</span>
}).then(<span class="variable">success</span> => console.log(<span class="string">'Student registered:'</span>, <span class="variable">success</span>));

<span class="comment">// Register a teacher</span>
<span class="function">registerUser</span>({
  role: <span class="string">'teacher'</span>,
  fullName: <span class="string">'Prof. Juan Dela Cruz'</span>,
  subject: <span class="string">'Computer Science'</span>,
  email: <span class="string">'juan.cruz@university.edu'</span>,
  password: <span class="string">'TeacherPass456!'</span>
}).then(<span class="variable">success</span> => console.log(<span class="string">'Teacher registered:'</span>, <span class="variable">success</span>));</div>
        </div>
    </div>

    <!-- PHP Example -->
    <div class="api-section">
        <h2><span class="method">PHP</span> cURL Example</h2>
        <div class="content">
<div class="code-block"><span class="keyword"><?php</span>
<span class="comment">/**
 * Register a new user programmatically in PHP
 */</span>

<span class="keyword">function</span> <span class="function">registerUser</span>(<span class="variable">$userData</span>) {
    <span class="variable">$baseUrl</span> = <span class="string">'<?php echo BASE_URL; ?>'</span>;
    <span class="variable">$registerUrl</span> = <span class="variable">$baseUrl</span> . <span class="string">'Registration/register.php'</span>;

    <span class="comment">// Step 1: Get CSRF token</span>
    <span class="variable">$ch</span> = curl_init(<span class="variable">$registerUrl</span>);
    curl_setopt(<span class="variable">$ch</span>, CURLOPT_RETURNTRANSFER, <span class="keyword">true</span>);
    curl_setopt(<span class="variable">$ch</span>, CURLOPT_COOKIEJAR, <span class="string">'cookies.txt'</span>);
    curl_setopt(<span class="variable">$ch</span>, CURLOPT_COOKIEFILE, <span class="string">'cookies.txt'</span>);
    <span class="variable">$html</span> = curl_exec(<span class="variable">$ch</span>);
    curl_close(<span class="variable">$ch</span>);

    preg_match(<span class="string">'/value="([a-f0-9]{64})"/'</span>, <span class="variable">$html</span>, <span class="variable">$matches</span>);
    <span class="variable">$csrfToken</span> = <span class="variable">$matches</span>[1] ?? <span class="string">''</span>;

    <span class="comment">// Step 2: Build POST data</span>
    <span class="variable">$postData</span> = [
        <span class="string">'csrf_token'</span>      => <span class="variable">$csrfToken</span>,
        <span class="string">'role'</span>            => <span class="variable">$userData</span>[<span class="string">'role'</span>],
        <span class="string">'full_name'</span>       => <span class="variable">$userData</span>[<span class="string">'full_name'</span>],
        <span class="string">'email'</span>           => <span class="variable">$userData</span>[<span class="string">'email'</span>],
        <span class="string">'password'</span>        => <span class="variable">$userData</span>[<span class="string">'password'</span>],
        <span class="string">'confirm_password'</span>=> <span class="variable">$userData</span>[<span class="string">'password'</span>],
    ];

    <span class="keyword">if</span> (<span class="variable">$userData</span>[<span class="string">'role'</span>] === <span class="string">'student'</span>) {
        <span class="variable">$postData</span>[<span class="string">'student_id'</span>] = <span class="variable">$userData</span>[<span class="string">'student_id'</span>] ?? <span class="string">''</span>;
    } <span class="keyword">elseif</span> (<span class="variable">$userData</span>[<span class="string">'role'</span>] === <span class="string">'teacher'</span>) {
        <span class="variable">$postData</span>[<span class="string">'subject'</span>] = <span class="variable">$userData</span>[<span class="string">'subject'</span>] ?? <span class="string">''</span>;
    }

    <span class="comment">// Step 3: Submit registration</span>
    <span class="variable">$ch</span> = curl_init(<span class="variable">$registerUrl</span>);
    curl_setopt(<span class="variable">$ch</span>, CURLOPT_POST, <span class="keyword">true</span>);
    curl_setopt(<span class="variable">$ch</span>, CURLOPT_POSTFIELDS, http_build_query(<span class="variable">$postData</span>));
    curl_setopt(<span class="variable">$ch</span>, CURLOPT_RETURNTRANSFER, <span class="keyword">true</span>);
    curl_setopt(<span class="variable">$ch</span>, CURLOPT_COOKIEJAR, <span class="string">'cookies.txt'</span>);
    curl_setopt(<span class="variable">$ch</span>, CURLOPT_COOKIEFILE, <span class="string">'cookies.txt'</span>);
    <span class="variable">$response</span> = curl_exec(<span class="variable">$ch</span>);
    curl_close(<span class="variable">$ch</span>);

    <span class="keyword">return</span> strpos(<span class="variable">$response</span>, <span class="string">'alert-success'</span>) !== <span class="keyword">false</span>;
}

<span class="comment">// Example: Register a student</span>
<span class="variable">$studentRegistered</span> = <span class="function">registerUser</span>([
    <span class="string">'role'</span>       => <span class="string">'student'</span>,
    <span class="string">'full_name'</span>  => <span class="string">'Maria Clara Santos'</span>,
    <span class="string">'student_id'</span> => <span class="string">'2024-00042'</span>,
    <span class="string">'email'</span>      => <span class="string">'maria.santos@university.edu'</span>,
    <span class="string">'password'</span>   => <span class="string">'StudentPass123!'</span>,
]);

echo <span class="variable">$studentRegistered</span> ? <span class="string">"Student registered successfully!\n"</span> : <span class="string">"Student registration failed.\n"</span>;

<span class="comment">// Example: Register a teacher</span>
<span class="variable">$teacherRegistered</span> = <span class="function">registerUser</span>([
    <span class="string">'role'</span>      => <span class="string">'teacher'</span>,
    <span class="string">'full_name'</span> => <span class="string">'Prof. Juan Dela Cruz'</span>,
    <span class="string">'subject'</span>   => <span class="string">'Computer Science'</span>,
    <span class="string">'email'</span>     => <span class="string">'juan.cruz@university.edu'</span>,
    <span class="string">'password'</span>  => <span class="string">'TeacherPass456!'</span>,
]);

echo <span class="variable">$teacherRegistered</span> ? <span class="string">"Teacher registered successfully!\n"</span> : <span class="string">"Teacher registration failed.\n"</span>;
<span class="keyword">?></span></div>
        </div>
    </div>

    <!-- Response Examples -->
    <div class="api-section">
        <h2>&#128172; Response Handling</h2>
        <div class="content">
            <p class="text-slate-top-margin">
                The registration endpoint returns an HTML page. To determine success or failure programmatically:
            </p>
            <div class="response-box">
                <h4>&#9989; Success Indicator</h4>
<p class="plain-text">Response HTML contains <code><div class="alert alert-success"></code></p>
            </div>
            <div class="response-box error">
                <h4>&#10060; Error Indicators</h4>
<p class="plain-text">Response HTML contains <code><div class="alert alert-error"></code> with messages like:</p>
                <ul class="bullet-list">
                    <li><code>"This email is already registered"</code></li>
                    <li><code>"Passwords do not match"</code></li>
                    <li><code>"Please enter the subject you teach"</code></li>
                    <li><code>"Security token expired"</code></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="examples-nav">
        <a href="<?php echo BASE_URL; ?>examples/signup_examples.php" class="btn btn-admin">&#128220; View Visual Examples</a>
        <a href="<?php echo BASE_URL; ?>Registration/register.php" class="btn btn-slate-margin">Go to Registration Form</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

