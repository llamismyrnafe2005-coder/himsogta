document.addEventListener("DOMContentLoaded",()=>{
 const KEY="kaabag_himsog_ta_demo_requests",list=document.getElementById("adminRequests"),filter=document.getElementById("statusFilter");
 const read=()=>{try{return JSON.parse(localStorage.getItem(KEY)||"[]")}catch{return []}};
 const esc=s=>String(s??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
 function render(){
  const requests=read();
  document.getElementById("totalCount").textContent=requests.length;
  document.getElementById("pendingCount").textContent=requests.filter(r=>r.status==="Pending").length;
  document.getElementById("approvedCount").textContent=requests.filter(r=>r.status==="Approved").length;
  document.getElementById("rejectedCount").textContent=requests.filter(r=>r.status==="Rejected").length;
  const shown=requests.filter(r=>filter.value==="All"||r.status===filter.value);
  if(!shown.length){list.innerHTML='<p class="empty-requests">No requests found for this filter.</p>';return;}
  list.innerHTML=shown.map(r=>`<article class="admin-request">
   <div class="admin-request-top"><div><strong>${esc(r.fullName)}</strong><small>${esc(r.id)} • ${esc(r.service)}</small></div><span class="status-${esc(r.status.toLowerCase())}">${esc(r.status)}</span></div>
   <div class="admin-request-details"><span><b>Student ID:</b> ${esc(r.studentId)}</span><span><b>Email:</b> ${esc(r.email)}</span><span><b>College:</b> ${esc(r.college)}</span><span><b>Schedule:</b> ${esc(r.date)} • ${esc(r.time)}</span></div>
   <p class="admin-reason"><b>Reason:</b> ${esc(r.reason)}</p>
   <label class="status-control">Update status<select data-request-id="${esc(r.id)}"><option ${r.status==="Pending"?"selected":""}>Pending</option><option ${r.status==="Approved"?"selected":""}>Approved</option><option ${r.status==="Rejected"?"selected":""}>Rejected</option></select></label>
  </article>`).join("");
  list.querySelectorAll("select[data-request-id]").forEach(sel=>sel.addEventListener("change",()=>{
   const all=read(),item=all.find(r=>r.id===sel.dataset.requestId);if(item){item.status=sel.value;localStorage.setItem(KEY,JSON.stringify(all));render();}
  }));
 }
 filter.addEventListener("change",render);render();
});