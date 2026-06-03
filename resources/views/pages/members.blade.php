<div id="membersArea">

  <!-- Loading state -->
  <div id="membersLoading" class="text-center py-5">
    <div class="spinner-border text-secondary" role="status">
      <span class="visually-hidden">Loading…</span>
    </div>
  </div>

  <!-- LOGIN GATE -->
  <div id="membersLogin" class="mx-auto" style="max-width:420px; display:none;">
    <h2 class="mb-1 text-center">Members Area</h2>
    <p class="text-center mb-4" style="color:#aab;">Lodge members can log in with their email address.</p>

    <!-- Step 1: email -->
    <div id="stepEmail">
      <form id="emailForm">
        <div class="mb-3">
          <label for="memberEmail" class="form-label">Email address</label>
          <input type="email" class="form-control members-input" id="memberEmail"
                 placeholder="you@example.com" required autocomplete="email">
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-primary" id="sendCodeBtn">Send Code</button>
        </div>
        <div id="emailError" class="alert alert-danger mt-3" style="display:none;"></div>
      </form>
    </div>

    <!-- Step 2: OTP -->
    <div id="stepOtp" style="display:none;">
      <p class="mb-3">A 6-digit code was sent to <strong id="emailDisplay"></strong>.</p>
      <form id="otpForm">
        <div class="mb-3">
          <label for="otpInput" class="form-label">One-time code</label>
          <input type="text" class="form-control members-input text-center"
                 id="otpInput" placeholder="000000" maxlength="6"
                 inputmode="numeric" pattern="\d{6}" required autocomplete="one-time-code"
                 style="font-size:1.5rem; letter-spacing:0.4em;">
        </div>
        <div class="d-grid mb-2">
          <button type="submit" class="btn btn-primary" id="verifyBtn">Verify</button>
        </div>
        <div class="text-center">
          <button type="button" class="btn btn-link btn-sm members-link" id="backToEmail">Use a different email</button>
        </div>
        <div id="otpError" class="alert alert-danger mt-3" style="display:none;"></div>
      </form>
    </div>
  </div>

  <!-- MEMBERS CONTENT -->
  <div id="membersContent" style="display:none;">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h2 class="mb-0">Members Area</h2>
      <button class="btn btn-outline-secondary btn-sm" id="logoutBtn">Log Out</button>
    </div>
    <iframe id="forumFrame"
            src=""
            style="width:100%; height:calc(100vh - 180px); border:none; border-radius:4px; display:block;"
            title="Members Forum"
            loading="lazy">
    </iframe>
  </div>

</div>

<script>
(function () {
  var currentEmail = '';

  function el(id) { return document.getElementById(id); }

  function showContent() {
    el('membersLoading').style.display  = 'none';
    el('membersLogin').style.display    = 'none';
    el('membersContent').style.display  = 'block';
    // Load the forum via SSO bridge (set src once — avoids reload on re-render)
    var frame = el('forumFrame');
    if (!frame.getAttribute('src')) {
      frame.setAttribute('src', 'https://forum.houstonheightslodge225.com/sso.php');
    }
  }

  function showLogin() {
    el('membersLoading').style.display  = 'none';
    el('membersLogin').style.display    = 'block';
    el('membersContent').style.display  = 'none';
    showStep('email');
  }

  function showStep(step) {
    el('stepEmail').style.display = step === 'email' ? 'block' : 'none';
    el('stepOtp').style.display   = step === 'otp'   ? 'block' : 'none';
    clearErrors();
  }

  function clearErrors() {
    ['emailError', 'otpError'].forEach(function (id) {
      el(id).style.display = 'none';
      el(id).textContent   = '';
    });
  }

  function showError(id, msg) {
    el(id).textContent   = msg;
    el(id).style.display = 'block';
  }

  function apiPost(path, body) {
    return fetch(path, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(body)
    }).then(function (r) {
      return r.json().then(function (data) {
        if (!r.ok) throw data;
        return data;
      });
    });
  }

  // Check session auth on every page load
  fetch('/api/check-auth.php', { credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.authenticated) showContent();
      else showLogin();
    })
    .catch(function () { showLogin(); });

  // Email form
  el('emailForm').addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();
    currentEmail = el('memberEmail').value.trim();
    var btn = el('sendCodeBtn');
    btn.disabled    = true;
    btn.textContent = 'Sending…';

    apiPost('/api/send-otp.php', { email: currentEmail })
      .then(function () {
        btn.disabled    = false;
        btn.textContent = 'Send Code';
        el('emailDisplay').textContent = currentEmail;
        showStep('otp');
      })
      .catch(function (err) {
        btn.disabled    = false;
        btn.textContent = 'Send Code';
        showError('emailError', (err && err.error) || 'Something went wrong. Please try again.');
      });
  });

  // OTP form
  el('otpForm').addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();
    var otp = el('otpInput').value.replace(/\D/g, '');
    var btn = el('verifyBtn');
    btn.disabled    = true;
    btn.textContent = 'Verifying…';

    apiPost('/api/verify-otp.php', { email: currentEmail, otp: otp })
      .then(function () {
        btn.disabled    = false;
        btn.textContent = 'Verify';
        showContent();
      })
      .catch(function (err) {
        btn.disabled    = false;
        btn.textContent = 'Verify';
        showError('otpError', (err && err.error) || 'Invalid code. Please try again.');
      });
  });

  // Back button
  el('backToEmail').addEventListener('click', function () {
    el('otpInput').value = '';
    showStep('email');
  });

  // Logout
  el('logoutBtn').addEventListener('click', function () {
    fetch('/api/logout.php', { method: 'POST', credentials: 'same-origin' })
      .finally(function () { showLogin(); });
  });
})();
</script>
