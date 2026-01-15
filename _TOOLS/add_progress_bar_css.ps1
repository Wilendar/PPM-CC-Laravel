# Add job progress bar CSS to components.css
$css = @"

/* ========================================
   JOB PROGRESS BAR - ETAP_07c
   PPM-style with animated borders
   ======================================== */

/* Base progress bar container */
.job-progress-bar {
    position: relative;
    background: rgba(15, 23, 42, 0.9);
    border-radius: 0.5rem;
    overflow: hidden;
    border: 2px solid transparent;
}

/* Animated border glow for running status */
.job-progress-bar--running {
    animation: progress-pulse-blue 2s ease-in-out infinite;
    box-shadow: 0 0 15px rgba(59, 130, 246, 0.3), inset 0 0 20px rgba(59, 130, 246, 0.05);
    border-color: rgba(59, 130, 246, 0.5);
}

/* Pulsing yellow border for awaiting_user */
.job-progress-bar--awaiting {
    animation: progress-pulse-yellow 1.5s ease-in-out infinite;
    box-shadow: 0 0 20px rgba(234, 179, 8, 0.4), inset 0 0 25px rgba(234, 179, 8, 0.05);
    border-color: rgba(234, 179, 8, 0.6);
}

/* Green glow for completed */
.job-progress-bar--completed {
    box-shadow: 0 0 15px rgba(34, 197, 94, 0.3), inset 0 0 20px rgba(34, 197, 94, 0.05);
    border-color: rgba(34, 197, 94, 0.5);
}

/* Red glow for failed */
.job-progress-bar--failed {
    box-shadow: 0 0 15px rgba(239, 68, 68, 0.3), inset 0 0 20px rgba(239, 68, 68, 0.05);
    border-color: rgba(239, 68, 68, 0.5);
}

/* Gray for pending */
.job-progress-bar--pending {
    box-shadow: 0 0 10px rgba(148, 163, 184, 0.2);
    border-color: rgba(148, 163, 184, 0.3);
}

/* Keyframes for pulsing animations */
@keyframes progress-pulse-blue {
    0%, 100% {
        box-shadow: 0 0 15px rgba(59, 130, 246, 0.3), inset 0 0 20px rgba(59, 130, 246, 0.05);
        border-color: rgba(59, 130, 246, 0.5);
    }
    50% {
        box-shadow: 0 0 25px rgba(59, 130, 246, 0.5), inset 0 0 30px rgba(59, 130, 246, 0.1);
        border-color: rgba(59, 130, 246, 0.7);
    }
}

@keyframes progress-pulse-yellow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(234, 179, 8, 0.4), inset 0 0 25px rgba(234, 179, 8, 0.05);
        border-color: rgba(234, 179, 8, 0.6);
    }
    50% {
        box-shadow: 0 0 35px rgba(234, 179, 8, 0.6), inset 0 0 40px rgba(234, 179, 8, 0.1);
        border-color: rgba(234, 179, 8, 0.9);
    }
}
"@

Add-Content -Path "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\css\admin\components.css" -Value $css -Encoding UTF8
Write-Host "CSS added to components.css" -ForegroundColor Green
