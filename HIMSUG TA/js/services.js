const serviceDetails = {
  prescription: {
    number: "SERVICE 01",
    icon: "Rx",
    title: "Prescription Refill",
    description: "This service is intended to assist students who need to request a refill for medication previously prescribed by a healthcare provider. It can help students ask about the proper process for continuing their prescribed treatment.",
    requirements: [
      "Valid USTP student ID.",
      "A current prescription from a licensed healthcare provider.",
      "Previous prescription or medication details, if available.",
      "Ask the clinic whether the prescribed medicine is available and whether a refill can be accommodated."
    ]
  },
  physical: {
    number: "SERVICE 02",
    icon: "✚",
    title: "Physical Examination",
    description: "A physical examination is a health assessment that helps evaluate a student's general physical condition. Depending on the purpose of the visit, it may include checking vital signs and other appropriate health indicators.",
    requirements: [
      "Valid USTP student ID.",
      "Medical examination form, if one is required for your purpose.",
      "Previous medical records or laboratory results, if available.",
      "Additional documents requested by the clinic for the specific examination."
    ]
  },
  dental: {
    number: "SERVICE 03",
    icon: "✦",
    title: "Dental Services",
    description: "Dental services support students in maintaining good oral health. Students may ask the clinic about available dental consultations, assessments, and procedures.",
    requirements: [
      "Valid USTP student ID.",
      "Appointment or consultation schedule, if required.",
      "Previous dental records, if available.",
      "Inform the dental personnel about any current dental concerns or relevant medical information."
    ]
  },
  psychological: {
    number: "SERVICE 04",
    icon: "♡",
    title: "Psychological Services",
    description: "Psychological services may provide students with professional support in understanding thoughts, emotions, and behavior. The type of support or assessment depends on the service available and the student's needs.",
    requirements: [
      "Valid USTP student ID, if requested.",
      "Appointment or referral, if required.",
      "Completed intake or assessment form, if applicable.",
      "Share only the information you feel comfortable providing and that is relevant to the consultation."
    ]
  },
  guidance: {
    number: "SERVICE 05",
    icon: "☼",
    title: "Guidance Counseling",
    description: "Guidance counseling offers students a supportive setting to discuss personal, academic, social, or emotional concerns. A guidance professional can help students explore options and make informed decisions.",
    requirements: [
      "Valid USTP student ID, if requested.",
      "Appointment or referral, if required.",
      "Completed counseling form, if applicable.",
      "You may prepare a brief description of the concern you wish to discuss, but sharing is at your comfort level."
    ]
  }
};

const modal = document.getElementById("serviceModal");
const modalTitle = document.getElementById("modalTitle");
const modalNumber = document.getElementById("modalNumber");
const modalIcon = document.getElementById("modalIcon");
const modalDescription = document.getElementById("modalDescription");
const modalRequirements = document.getElementById("modalRequirements");
const closeButtons = [document.getElementById("modalClose"), document.getElementById("modalDone")];

function openServiceDetails(serviceKey) {
  const service = serviceDetails[serviceKey];
  if (!service) return;

  modalNumber.textContent = service.number;
  modalIcon.textContent = service.icon;
  modalTitle.textContent = service.title;
  modalDescription.textContent = service.description;
  modalRequirements.replaceChildren();

  service.requirements.forEach((requirement) => {
    const item = document.createElement("li");
    item.textContent = requirement;
    modalRequirements.appendChild(item);
  });

  modal.hidden = false;
  document.body.style.overflow = "hidden";
  document.getElementById("modalClose").focus();
}

function closeServiceDetails() {
  modal.hidden = true;
  document.body.style.overflow = "";
}

document.querySelectorAll(".learn-more").forEach((button) => {
  button.addEventListener("click", () => openServiceDetails(button.dataset.service));
});

closeButtons.forEach((button) => button.addEventListener("click", closeServiceDetails));

modal.addEventListener("click", (event) => {
  if (event.target === modal) closeServiceDetails();
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && !modal.hidden) closeServiceDetails();
});

const searchInput = document.getElementById("serviceSearch");
const serviceCards = [...document.querySelectorAll(".service-card")];
const noResults = document.getElementById("noResults");

searchInput.addEventListener("input", () => {
  const searchTerm = searchInput.value.trim().toLowerCase();
  let visibleCount = 0;

  serviceCards.forEach((card) => {
    const searchableText = `${card.dataset.search} ${card.textContent}`.toLowerCase();
    const isMatch = searchableText.includes(searchTerm);
    card.hidden = !isMatch;
    if (isMatch) visibleCount += 1;
  });

  noResults.hidden = visibleCount !== 0;
});

const menuToggle = document.getElementById("menuToggle");
const mainNav = document.getElementById("mainNav");

menuToggle.addEventListener("click", () => {
  const isOpen = mainNav.classList.toggle("open");
  menuToggle.setAttribute("aria-expanded", String(isOpen));
  menuToggle.setAttribute("aria-label", isOpen ? "Close navigation" : "Open navigation");
  menuToggle.textContent = isOpen ? "×" : "☰";
});
