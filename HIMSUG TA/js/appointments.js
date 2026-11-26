document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("appointmentForm");
  if (!form) return;

  const dateInput = document.getElementById("appointmentDate");
  const today = new Date();
  const localToday = new Date(today.getTime() - today.getTimezoneOffset() * 60000)
    .toISOString().split("T")[0];
  dateInput.min = localToday;

  const confirmation = document.getElementById("requestConfirmation");
  const requestList = document.getElementById("requestList");
  const storageKey = "kaabag_himsog_ta_demo_requests";

  function getRequests() {
    try { return JSON.parse(localStorage.getItem(storageKey) || "[]"); }
    catch { return []; }
  }

  function renderRequests() {
    const requests = getRequests();
    if (!requests.length) {
      requestList.innerHTML = '<p class="empty-requests">No demo requests submitted yet.</p>';
      return;
    }
    requestList.innerHTML = requests.map(item => `
      <article class="request-item">
        <div><strong>${escapeHTML(item.service)}</strong><small>${escapeHTML(item.date)} • ${escapeHTML(item.time)}</small></div>
        <span class="status-pending">${escapeHTML(item.status)}</span>
        <small>Request ID: ${escapeHTML(item.id)}</small>
      </article>`).join("");
  }

  function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, char => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    }[char]));
  }

  form.addEventListener("submit", event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const data = new FormData(form);
    const request = {
      id: "KBG-" + Date.now().toString().slice(-8),
      fullName: data.get("fullName").trim(),
      studentId: data.get("studentId").trim(),
      email: data.get("email").trim(),
      college: data.get("college").trim(),
      service: data.get("service"),
      date: data.get("date"),
      time: data.get("time"),
      reason: data.get("reason").trim(),
      status: "Pending"
    };
    const requests = getRequests();
    requests.unshift(request);
    localStorage.setItem(storageKey, JSON.stringify(requests));
    confirmation.hidden = false;
    confirmation.innerHTML = `<strong>Demo request saved!</strong><p>Your request ID is <b>${escapeHTML(request.id)}</b>. Its demonstration status is <b>Pending</b>.</p><p>This request has only been saved in this browser. It has <b>not</b> been sent to the USTP School Clinic.</p>`;
    form.reset();
    dateInput.min = localToday;
    renderRequests();
    confirmation.scrollIntoView({ behavior: "smooth", block: "center" });
  });

  document.getElementById("clearRequests").addEventListener("click", () => {
    if (confirm("Clear all appointment requests saved in this browser for the demonstration?")) {
      localStorage.removeItem(storageKey);
      confirmation.hidden = true;
      renderRequests();
    }
  });

  renderRequests();
});