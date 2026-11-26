document.addEventListener("DOMContentLoaded", () => {
 const USERS_KEY="kaabag_himsog_ta_demo_users";
 const getUsers=()=>{try{return JSON.parse(localStorage.getItem(USERS_KEY)||"[]")}catch{return []}};
 const setMessage=(el,msg,ok=false)=>{el.textContent=msg;el.className="account-message "+(ok?"success":"error")};
 const register=document.getElementById("registerForm");
 if(register) register.addEventListener("submit",e=>{
  e.preventDefault(); if(!register.reportValidity())return;
  const d=new FormData(register), email=d.get("email").trim().toLowerCase();
  if(d.get("password")!==d.get("confirmPassword"))return setMessage(document.getElementById("registerMessage"),"Passwords do not match.");
  const users=getUsers(); if(users.some(u=>u.email===email))return setMessage(document.getElementById("registerMessage"),"An account with this email already exists in this browser.");
  users.push({fullName:d.get("fullName").trim(),studentId:d.get("studentId").trim(),email,college:d.get("college").trim(),yearLevel:d.get("yearLevel"),password:d.get("password")});
  localStorage.setItem(USERS_KEY,JSON.stringify(users));setMessage(document.getElementById("registerMessage"),"Demo account created! You can now log in.",true);register.reset();
 });
 const login=document.getElementById("loginForm");
 if(login) login.addEventListener("submit",e=>{
  e.preventDefault();if(!login.reportValidity())return;
  const d=new FormData(login),email=d.get("email").trim().toLowerCase();
  const user=getUsers().find(u=>u.email===email&&u.password===d.get("password"));
  if(!user)return setMessage(document.getElementById("loginMessage"),"Account not found or password is incorrect. Register a demo account first.");
  sessionStorage.setItem("kaabag_himsog_ta_demo_session",JSON.stringify({email:user.email,fullName:user.fullName}));
  setMessage(document.getElementById("loginMessage"),"Welcome, "+user.fullName+"! Demo login successful. This is not an official USTP account.",true);
 });
});