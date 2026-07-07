<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Minimal Rounded Tabs</title>
  <style>
    body {
      font-family: "Segoe UI", sans-serif;
      background-color: #f3f4f6;
      margin: 0;
      padding: 40px;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .tab-container {
      width: 600px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    .tab-buttons {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
      border-bottom: 2px solid #ddd;
    }

    .tab-button {
      border: none;
      outline: none;
      padding: 15px 30px;
      font-size: 16px;
      background-color: #f4f4f4;
      color: #555;
      border-radius: 30px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .tab-button svg {
      width: 20px;
      height: 20px;
      stroke: currentColor;
    }

    .tab-button.active {
      background-color: #0d6efd;
      color: #fff;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .tab-content {
      padding: 20px;
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    /* Styling for active tab content */
    .tab-content h2 {
      font-size: 24px;
      color: #333;
    }

    .tab-content p {
      color: #555;
      line-height: 1.6;
    }
  </style>
</head>
<body>

<div class="tab-container">
  <div class="tab-buttons">
    <button class="tab-button active" data-tab="designer">
      <!-- Feather Icon: Pen Tool -->
      <svg fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M12 19l-3 3 2.5-7.5L3 12l7.5-2.5L12 2l3 7.5L22 12l-7.5 2.5L15 22z"/>
      </svg>
      Designer
    </button>
    <button class="tab-button" data-tab="developer">
      <!-- Feather Icon: Code -->
      <svg fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <polyline points="16 18 22 12 16 6"></polyline>
        <polyline points="8 6 2 12 8 18"></polyline>
      </svg>
      Developer
    </button>
    <button class="tab-button" data-tab="preview">
      <!-- Feather Icon: Eye -->
      <svg fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
        <circle cx="12" cy="12" r="3"></circle>
      </svg>
      Preview
    </button>
  </div>

  <div id="designer" class="tab-content active">
    <h2>Designer Panel</h2>
    <p>Tools for layout, styles, and design elements.</p>
  </div>

  <div id="developer" class="tab-content">
    <h2>Developer Panel</h2>
    <p>Code integrations, API tools, and advanced settings.</p>
  </div>

  <div id="preview" class="tab-content">
    <h2>Preview Panel</h2>
    <p>See your layout in real-time.</p>
  </div>
</div>

<script>
  const buttons = document.querySelectorAll('.tab-button');
  const contents = document.querySelectorAll('.tab-content');

  buttons.forEach(button => {
    button.addEventListener('click', () => {
      buttons.forEach(btn => btn.classList.remove('active'));
      contents.forEach(tab => tab.classList.remove('active'));

      button.classList.add('active');
      document.getElementById(button.getAttribute('data-tab')).classList.add('active');
    });
  });
</script>

</body>
</html>
