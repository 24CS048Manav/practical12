<?php
// events.php - single-file CRUD app for events
// DB config - change if needed
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = ''; // XAMPP default is empty
$DB_NAME = 'prac12';

// connect
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// messages
$errors = [];
$success = "";

// Handle POST actions: create, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $date = $_POST['event_date'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '' || $date === '' || $location === '') {
            $errors[] = "Name, date and location are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO events (name, event_date, location, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $date, $location, $description);
            if ($stmt->execute()) {
                $success = "Event added successfully.";
            } else {
                $errors[] = "Insert failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $date = $_POST['event_date'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$id || $name === '' || $date === '' || $location === '') {
            $errors[] = "All fields are required.";
        } else {
            $stmt = $conn->prepare("UPDATE events SET name = ?, event_date = ?, location = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $date, $location, $description, $id);
            if ($stmt->execute()) {
                $success = "Event updated successfully.";
            } else {
                $errors[] = "Update failed: " . $stmt->error;
            }
            $stmt->close();
            // after update, redirect to avoid resubmission on refresh
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $success = "Event deleted.";
            } else {
                $errors[] = "Delete failed: " . $stmt->error;
            }
            $stmt->close();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $errors[] = "Invalid ID for delete.";
        }
    }
}

// If editing, load event into $edit_event
$edit_event = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    if ($edit_id) {
        $stmt = $conn->prepare("SELECT id, name, event_date, location, description FROM events WHERE id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $edit_event = $res->fetch_assoc();
        $stmt->close();
    }
}

// Fetch all events
$events = [];
$res = $conn->query("SELECT id, name, event_date, location, description, created_at FROM events ORDER BY event_date ASC, id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $events[] = $row;
    }
    $res->free();
}

$conn->close();

// helper - escape output
function e($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Event Manager - CRUD (single file)</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  /* Simple, clean styling */
  body { font-family: Arial, sans-serif; background: #f5f7fb; color: #222; margin: 0; padding: 20px; }
  .container { max-width: 1000px; margin: 0 auto; }
  header { background: #2b74d4; color: #fff; padding: 18px; border-radius: 8px; margin-bottom: 18px; }
  h1 { margin: 0; font-size: 20px; }
  .card { background: #fff; padding: 16px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 12px; }
  form .row { display: flex; gap: 10px; flex-wrap: wrap; }
  label { display: block; font-size: 13px; margin-bottom: 6px; }
  input[type="text"], input[type="date"], textarea { width: 100%; padding: 8px; border: 1px solid #d6dbe6; border-radius: 6px; }
  textarea { min-height: 80px; resize: vertical; }
  .col { flex: 1 1 200px; min-width: 200px; }
  .actions { margin-top: 10px; display:flex; gap:8px; align-items:center; }
  button { padding: 8px 12px; border-radius: 6px; border: none; cursor: pointer; }
  .btn-primary { background: #2b74d4; color: #fff; }
  .btn-secondary { background: #eef3fb; color: #2b74d4; border: 1px solid #d6e3fb; }
  .btn-danger { background: #f44336; color: #fff; }
  table { width: 100%; border-collapse: collapse; margin-top: 12px; }
  th, td { text-align: left; padding: 10px; border-bottom: 1px solid #eee; vertical-align: top; }
  .muted { color: #666; font-size: 13px; }
  .msg { padding: 10px; border-radius: 6px; margin-bottom: 12px; }
  .msg.error { background: #ffecec; color: #cc0000; border: 1px solid #f5b5b5; }
  .msg.success { background: #e7f8ee; color: #076738; border: 1px solid #b9e5c4; }
  @media (max-width:700px) { .row { flex-direction: column; } }
</style>
</head>
<body>
<div class="container">
  <header>
    <h1>Event Manager</h1>
    <!-- <div class="muted">Add, edit and delete events. Data stored in MySQL.</div> -->
  </header>

  <?php if (!empty($errors)): ?>
    <div class="msg error">
      <?php foreach ($errors as $err) echo "<div>" . e($err) . "</div>"; ?>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="msg success"><?php echo e($success); ?></div>
  <?php endif; ?>

  <div class="card">
    <h3><?php echo $edit_event ? "Edit Event" : "Add New Event"; ?></h3>
    <form method="post" action="">
      <input type="hidden" name="action" value="<?php echo $edit_event ? 'update' : 'create'; ?>">
      <?php if ($edit_event): ?>
        <input type="hidden" name="id" value="<?php echo (int)$edit_event['id']; ?>">
      <?php endif; ?>
      <div class="row">
        <div class="col">
          <label for="name">Event Name</label>
          <input id="name" name="name" type="text" value="<?php echo $edit_event ? e($edit_event['name']) : ''; ?>" required>
        </div>
        <div class="col">
          <label for="event_date">Date</label>
          <input id="event_date" name="event_date" type="date" value="<?php echo $edit_event ? e($edit_event['event_date']) : ''; ?>" required>
        </div>
        <div class="col">
          <label for="location">Location</label>
          <input id="location" name="location" type="text" value="<?php echo $edit_event ? e($edit_event['location']) : ''; ?>" required>
        </div>
      </div>

      <div style="margin-top:10px">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?php echo $edit_event ? e($edit_event['description']) : ''; ?></textarea>
      </div>

      <div class="actions">
        <button type="submit" class="btn-primary"><?php echo $edit_event ? 'Update Event' : 'Add Event'; ?></button>
        <?php if ($edit_event): ?>
          <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn-secondary" style="display:inline-block;padding:8px 12px;text-decoration:none;border-radius:6px;">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="card">
    <h3>All Events (<?php echo count($events); ?>)</h3>
    <?php if (count($events) === 0): ?>
      <div class="muted">No events yet. Add one above.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Event</th>
            <th>Date</th>
            <th>Location</th>
            <th>Description</th>
            <th style="width:170px">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($events as $ev): ?>
          <tr>
            <td><strong><?php echo e($ev['name']); ?></strong><div class="muted">Created: <?php echo e($ev['created_at']); ?></div></td>
            <td><?php echo e($ev['event_date']); ?></td>
            <td><?php echo e($ev['location']); ?></td>
            <td><?php echo nl2br(e($ev['description'])); ?></td>
            <td>
              <a class="btn-secondary" href="<?php echo $_SERVER['PHP_SELF'] . '?edit=' . (int)$ev['id']; ?>" style="text-decoration:none;padding:6px 8px;border-radius:6px;">Edit</a>
              <form method="post" action="" style="display:inline-block;margin-left:6px;" onsubmit="return confirm('Delete this event?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$ev['id']; ?>">
                <button type="submit" class="btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <footer style="text-align:center;color:#777;margin-top:18px;font-size:13px">
    Add events | Delete events | Edit events
  </footer>
</div>
</body>
</html>
