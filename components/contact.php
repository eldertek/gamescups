<div class="row justify-content-evenly bg-dark text-white mt-3">
<!-- NOUS CONTACTER -->
<div class="col-4">
  <h1 class="text-center mt-3">Nous contacter</h1>
  <form action="/admin/messages.php" method="post">
  <?= function_exists('gamescups_csrf_field') ? gamescups_csrf_field() : '' ?>
  <div class="mb-3">
    <label for="email" class="mt-2 form-label">Adresse mail :</label>
    <input type="email" class="form-control" id="email" name="email" placeholder="john.doe@example.com" required>
  </div>
  <div class="mb-3">
    <label for="message" class="mt-0 form-label">Message :</label>
    <textarea class="form-control" id="message" rows="3" name="message" required></textarea>
  </div>
  <div class="mb-3">
    <button class="form-control" type="submit">Prendre contact !</button>
  </div>
  </form>
</div>
<!-- NOS PARTENAIRES -->
<div class="col-4">
  <h1 class="text-center mt-3">Nos partenaires</h1>
  <div class="row row-cols-1 row-cols-md-2 g-4 mt-3">
    <?php
      if (isset($connect) && $connect instanceof PDO) {
          foreach ($connect->query('SELECT image, website FROM partenaires ORDER BY id DESC') as $row) {
              $website = gamescups_safe_url(isset($row['website']) ? (string) $row['website'] : null, false);
              $image = gamescups_safe_url(isset($row['image']) ? (string) $row['image'] : null, true);
              if ($image === '') {
                  continue;
              }
              echo '
            <div class="col">
              <div class="card">
                <a target="_blank" rel="noopener noreferrer" href="'.gamescups_e($website).'"><img src="'.gamescups_e($image).'" class="card-img-top" alt="Partenaire GamesCups"></a>
              </div>
            </div>';
          }
      }
    ?>
  </div>
</div>
</div>
