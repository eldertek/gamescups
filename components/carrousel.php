<div class="carrousel">
  <?php
  if (isset($connect) && $connect instanceof PDO) {
      foreach ($connect->query('SELECT slider FROM sliders ORDER BY id DESC') as $row) {
          $sliderUrl = gamescups_safe_url(isset($row['slider']) ? (string) $row['slider'] : null, true);
          if ($sliderUrl === '') {
              continue;
          }
          echo '
        <div class="slideshow slidefade">
          <img src="'.gamescups_e($sliderUrl).'" alt="Banniere GamesCups">
        </div>';
      }
  }
  ?>
</div>

<script type="text/javascript">
  let slideshowIndex = 1;
  showSlides(slideshowIndex);

  function showSlides(n) {
    const slides = document.getElementsByClassName("slideshow");
    if (slides.length === 0) {
      return;
    }
    if (n > slides.length) {
      slideshowIndex = 1;
    }
    for (let i = 0; i < slides.length; i++) {
      slides[i].style.display = "none";
    }
    slides[slideshowIndex - 1].style.display = "block";
  }

  setInterval(() => {
    showSlides(slideshowIndex += 1);
  }, 4000);
</script>
