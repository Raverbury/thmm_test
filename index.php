<!DOCTYPE html>
<html>

<head>
  <title>Main</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.3/howler.min.js" integrity="sha512-6+YN/9o9BWrk6wSfGxQGpt3EUK6XeHi6yeHV+TYD2GR0Sj/cggRpXr1BrAQf0as6XslxomMUxXp2vIl+fv0QRA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <style>
    .round-time-bar {
      margin: 1rem;
      overflow: hidden;
    }

    .round-time-bar div {
      height: 5px;
      animation: roundtime calc(var(--duration) * 1s) steps(var(--duration)) forwards;
      transform-origin: left center;
      background: linear-gradient(to bottom, red, #900);
    }

    .bar-static div {
      height: 5px;
      transform-origin: left center;
      background: linear-gradient(to bottom, red, #900);
      margin: 1rem;
      overflow: hidden;
    }

    .round-time-bar[data-style="smooth"] div {
      animation: roundtime calc(var(--duration) * 1s) linear forwards;
    }

    .round-time-bar[data-style="fixed"] div {
      width: calc(var(--duration) * 5%);
    }

    .round-time-bar[data-color="blue"] div {
      background: linear-gradient(to bottom, #64b5f6, #1565c0);
    }

    #upload {
      width: 100%;
    }

    @keyframes roundtime {
      to {
        /* More performant than `width` */
        transform: scaleX(0);
      }
    }
  </style>
</head>

<body>

  <div class="container my-4">
    <input type="file" id="upload" />
    <audio id="audio" controls style="display: none;">
      <source src="" id="src" />
    </audio>

    <div id="bar" data-style="smooth" style="--duration: 2;" class="bar-static">
      <div></div>
    </div>

    <div class="btn-group" role="group" aria-label="Controls">
      <button type="button" class="btn btn-success" id="recordBtn">Start recording</button>
      <button type="button" class="btn btn-danger" id="stopBtn">Stop recording</button>
      <button type="button" class="btn btn-primary" id="replayBtn">Replay</button>
      <button type="button" class="btn btn-warning" id="exportBtn">Export</button>
    </div>

    <div class="container my-4">
      <canvas id="mapCanvas" width="1000" height="400"></canvas>
    </div>

  </div>

  <script>
    var countdownTask;

    var replayTask;
    var replayCountdownTask;
    var spawnTasks;

    var inReplayMode;

    var posArray = [];
    var timeArray = [];

    var startTimestamp;

    var isRecording;

    var canvas;
    var canvasContext;

    var tileQueue;

    var hitSound;

    const JUDGEMENT_LINE_DISTANCE = 0.8;
    const LANE_GAP = 400;
    const FPS = 60;

    const RECORD_READY_DELAY = 2;
    const REPLAY_TIME_TO_TARGET = 2;

    $(document).ready(function() {
      inReplayMode = false;
      isRecording = false;
      canvas = $('#mapCanvas')[0];
      canvasContext = canvas.getContext('2d');

      hitSound = new Howl({
        src: ['graze.mp3'],
        html5: true,
      });

      $(document).keydown(function(event) {
        if (isRecording == false) return;
        let code = event.which || event.keyCode;
        let nowTimestamp = getTimestamp();
        timeArray.push((nowTimestamp - startTimestamp) / 1000);
        switch (code) {
          case 37: // left arrow
            posArray.push(-LANE_GAP);
            break;
          case 39: // right arrow
            posArray.push(LANE_GAP);
            break;
          case 40: // down arrow
            posArray.push(0);
            break;
        }
      });

      $("#stopBtn").prop("disabled", true);
      $("#recordBtn").prop("disabled", true);
      $("#replayBtn").prop("disabled", true);
      $("#exportBtn").prop("disabled", true);

      $("#recordBtn").click(function() {
        $("#recordBtn").prop("disabled", true);
        $("#stopBtn").prop("disabled", false);
        $("#replayBtn").prop("disabled", true);
        $("#exportBtn").prop("disabled", true);
        posArray = [];
        timeArray = [];
        $("#bar")[0].classList.add("round-time-bar");
        $("#bar")[0].classList.remove("bar-static");
        countdownTask = setTimeout(() => {
          isRecording = true;
          startTimestamp = getTimestamp();
          $("#audio")[0].volume = 1;
          $("#audio")[0].play();
        }, RECORD_READY_DELAY * 1000);
      });

      $("#stopBtn").click(function() {
        clearTimeout(countdownTask);
        $("#audio")[0].pause();
        $("#audio")[0].currentTime = 0;
        $("#stopBtn").prop("disabled", true);
        $("#recordBtn").prop("disabled", false);
        $("#replayBtn").prop("disabled", false);
        $("#exportBtn").prop("disabled", false);
        isRecording = false;
        $("#bar")[0].classList.remove("round-time-bar");
        $("#bar")[0].classList.add("bar-static");
        $("#bar")[0].offsetWidth;
      });

      $("#replayBtn").click(function() {
        if (inReplayMode == false) {
          inReplayMode = true;
          startReplay();
        } else {
          inReplayMode = false;
          stopReplay();
        }
      });

      function startReplay() {
        $("#stopBtn").prop("disabled", true);
        $("#recordBtn").prop("disabled", true);
        $("#replayBtn").prop("disabled", false);
        $("#exportBtn").prop("disabled", true);
        replayTask = setInterval(updateCanvas, 1000 / FPS);
        tileQueue = [];
        replayCountdownTask = setTimeout(() => {
          $("#audio")[0].volume = 0.6;
          $("#audio")[0].play();
        }, REPLAY_TIME_TO_TARGET * 1000);
        spawnTasks = [];
        for (let index = 0; index < posArray.length; index++) {
          spawnTasks.push(setTimeout(() => {
            tileQueue.push(new Tile(posArray[index] / 2 + canvas.width * 0.5, 0));
            console.log('here');
          }, timeArray[index] * 1000));
        }
      }

      function stopReplay() {
        clearInterval(replayTask);
        clearTimeout(replayCountdownTask);
        for (let index = 0; index < spawnTasks.length; index++) {
          clearTimeout(spawnTasks[index]);
        }
        canvasContext.clearRect(0, 0, canvas.width, canvas.height);
        $("#audio")[0].pause();
        $("#audio")[0].currentTime = 0;
        $("#stopBtn").prop("disabled", true);
        $("#recordBtn").prop("disabled", false);
        $("#replayBtn").prop("disabled", false);
        $("#exportBtn").prop("disabled", false);
      }

      $("#exportBtn").click(function() {
        let obj = {
          tileSpawnTimes: timeArray,
          tileHorizontalOffsets: posArray
        }
        download(JSON.stringify(obj), 'json.txt', 'text/plain');
      });
    });

    function updateCanvas() {
      canvasContext.clearRect(0, 0, canvas.width, canvas.height);
      // draw background
      canvasContext.beginPath();
      canvasContext.rect(0, 0, canvas.width, canvas.height);
      canvasContext.fillStyle = "gray";
      canvasContext.fill();
      // draw judgement line
      canvasContext.beginPath();
      canvasContext.fillStyle = "black";
      canvasContext.rect(0, canvas.height * JUDGEMENT_LINE_DISTANCE, canvas.width, canvas.height * 0.05);
      canvasContext.fill();
      // draw the rest
      canvasContext.fillStyle = "white";
      for (let index = 0; index < tileQueue.length; index++) {
        let obj = tileQueue[index];
        obj.update();
        if (obj.die) {
          tileQueue.splice(index, 1);
        }
      }
    }

    function handleFiles(event) {
      var files = event.target.files;
      $("#src").attr("src", URL.createObjectURL(files[0]));
      $("#audio").load();
      $("#recordBtn").prop("disabled", false);
    }

    function download(content, fileName, contentType) {
      var a = document.createElement("a");
      var file = new Blob([content], {
        type: contentType
      });
      a.href = URL.createObjectURL(file);
      a.download = fileName;
      a.click();
    }

    if (window.performance.now) {
      console.log("Using high performance timer");
      getTimestamp = function() {
        return window.performance.now();
      };
    } else {
      if (window.performance.webkitNow) {
        console.log("Using webkit high performance timer");
        getTimestamp = function() {
          return window.performance.webkitNow();
        };
      } else {
        console.log("Using low performance timer");
        getTimestamp = function() {
          return new Date().getTime();
        };
      }
    }

    class Tile {
      constructor(x, y) {
        this.x = x;
        this.y = y;
        this.die = false;
        this.hit = false;
        this.color = "white";
      }

      update = () => {
        this.y += canvas.height * JUDGEMENT_LINE_DISTANCE / (REPLAY_TIME_TO_TARGET * FPS);
        canvasContext.beginPath();
        canvasContext.fillStyle = this.color;
        canvasContext.rect(this.x - canvas.width * 0.1, this.y, canvas.width * 0.2, canvas.height * 0.05);
        canvasContext.fill();
        if (this.hit == false && this.y >= canvas.height * JUDGEMENT_LINE_DISTANCE)
        {
          this.hit = true;
          this.color = "red";
          hitSound.play();
        }
        if (this.y >= canvas.height * 1.1) this.die = true;
      }
    }

    document.getElementById("upload").addEventListener("change", handleFiles, false);
  </script>

</body>

</html>