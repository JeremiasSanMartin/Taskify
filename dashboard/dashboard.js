// Dashboard JavaScript
document.addEventListener("DOMContentLoaded", () => {
  // Initialize dashboard
  initializeDashboard()

  handleSidebarNavigation()

  // Handle responsive sidebar
  handleResponsiveSidebar()
})

function initializeDashboard() {
  console.log("[v0] Dashboard initialized")

  // Show groups section by default
  showSection("grupos")
}

function handleSidebarNavigation() {
  const menuItems = document.querySelectorAll(".menu-item[data-section]")

  menuItems.forEach((item) => {
    item.addEventListener("click", (e) => {
      e.preventDefault()

      // Remove active class from all menu items
      menuItems.forEach((menuItem) => menuItem.classList.remove("active"))

      // Add active class to clicked item
      item.classList.add("active")

      // Get section to show
      const section = item.getAttribute("data-section")
      showSection(section)

      console.log(`[v0] Navigated to section: ${section}`)
    })
  })
}

function showSection(sectionName) {
  // Hide all sections
  const sections = document.querySelectorAll(".content-section")
  sections.forEach((section) => {
    section.classList.remove("active")
  })

  // Show selected section
  const targetSection = document.getElementById(`${sectionName}-section`)
  if (targetSection) {
    targetSection.classList.add("active")
  }

  // Update page title based on section
  const pageTitle = document.querySelector(".page-title")
  const pageSubtitle = document.querySelector(".page-subtitle")

  if (sectionName === "grupos") {
    pageTitle.textContent = "Mis Grupos"
    pageSubtitle.textContent = "Gestiona tus grupos y colaboraciones"
  } else if (sectionName === "notificaciones") {
    pageTitle.textContent = "Notificaciones"
    pageSubtitle.textContent = "Mantente al día con las últimas actualizaciones"
  }
}

function handleResponsiveSidebar() {
  // Add mobile menu toggle functionality
  const sidebar = document.querySelector(".sidebar")
  const mainContent = document.querySelector(".main-content")

  // Create mobile menu button
  if (window.innerWidth <= 768) {
    createMobileMenuButton()
  }

  window.addEventListener("resize", () => {
    if (window.innerWidth <= 768) {
      createMobileMenuButton()
    } else {
      removeMobileMenuButton()
    }
  })
}

function createMobileMenuButton() {
  if (document.querySelector(".mobile-menu-btn")) return

  const mobileBtn = document.createElement("button")
  mobileBtn.className = "mobile-menu-btn"
  mobileBtn.innerHTML = '<i class="bi bi-list"></i>'
  mobileBtn.style.cssText = `
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        width: 44px;
        height: 44px;
        background: #111111;
        border: 1px solid #1f1f1f;
        color: #ffffff;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    `

  document.body.appendChild(mobileBtn)

  mobileBtn.addEventListener("click", () => {
    const sidebar = document.querySelector(".sidebar")
    const isOpen = sidebar.style.transform === "translateX(0px)"

    if (isOpen) {
      sidebar.style.transform = "translateX(-100%)"
    } else {
      sidebar.style.transform = "translateX(0px)"
    }
  })
}

function removeMobileMenuButton() {
  const mobileBtn = document.querySelector(".mobile-menu-btn")
  if (mobileBtn) {
    mobileBtn.remove()
  }
}

// Group card interactions
document.addEventListener("click", (e) => {
  if (e.target.closest(".group-card")) {
    const groupCard = e.target.closest(".group-card")
    const groupName = groupCard.querySelector(".group-name").textContent
    console.log(`[v0] Clicked on group: ${groupName}`)
    // Here you would navigate to the group details page
  }
})

document.addEventListener("click", (e) => {
  if (e.target.closest(".logout-btn")) {
    console.log("[v0] Logout clicked")
    // Here you would handle the logout process
    if (confirm("¿Estás seguro de que quieres cerrar sesión?")) {
      window.location.href = "../index.html"
    }
  }
})
