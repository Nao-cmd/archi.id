// Tab switching functionality
function switchTab(tabName) {
  // Hide all tabs
  const tabs = document.querySelectorAll(".tab-content")
  tabs.forEach((tab) => tab.classList.remove("active"))

  // Remove active class from buttons
  const buttons = document.querySelectorAll(".tab-btn")
  buttons.forEach((btn) => btn.classList.remove("active"))

  // Show selected tab
  const selectedTab = document.getElementById(tabName)
  if (selectedTab) {
    selectedTab.classList.add("active")
  }

  // Add active class to clicked button
  event.target.classList.add("active")
}

// File upload drag and drop
const fileUploads = document.querySelectorAll(".file-upload")

fileUploads.forEach((upload) => {
  const input = upload.querySelector('input[type="file"]')

  upload.addEventListener("click", () => input.click())

  upload.addEventListener("dragover", (e) => {
    e.preventDefault()
    upload.style.backgroundColor = "#faf8f6"
    upload.style.borderColor = "#d4a574"
  })

  upload.addEventListener("dragleave", () => {
    upload.style.backgroundColor = ""
    upload.style.borderColor = ""
  })

  upload.addEventListener("drop", (e) => {
    e.preventDefault()
    upload.style.backgroundColor = ""
    upload.style.borderColor = ""

    const files = e.dataTransfer.files
    if (files.length > 0) {
      input.files = files
    }
  })
})

// User menu dropdown
const userMenuBtn = document.querySelector(".user-menu-btn")
const userDropdown = document.querySelector(".user-dropdown")

if (userMenuBtn) {
  userMenuBtn.addEventListener("click", (e) => {
    e.stopPropagation()
    userDropdown.style.display = userDropdown.style.display === "block" ? "none" : "block"
  })

  document.addEventListener("click", () => {
    userDropdown.style.display = "none"
  })
}
