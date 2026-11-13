@echo off
rem Richtet die Arbeitskopien des Komp OnePagers getrennt nebeneinander ein:
rem   C:\Temp\Komp\live       (Branch live, Arbeitsversion)
rem   C:\Temp\Komp\version-3  (Branch design-v3, Archiv)
rem   C:\Temp\Komp\version-4  (Branch design-v4, Archiv)
rem   C:\Temp\Komp\version-5  (Branch design-v5, Archiv)
rem Voraussetzung: Git fuer Windows (https://git-scm.com/download/win)

set REPO=https://github.com/AS-Git/komp-onepager-1.git
set ZIEL=C:\Temp\Komp

where git >nul 2>nul
if errorlevel 1 (
  echo Git ist nicht installiert. Bitte zuerst Git fuer Windows installieren.
  pause
  exit /b 1
)

if exist "%ZIEL%\.git" (
  echo In %ZIEL% liegt bereits eine Arbeitskopie direkt im Ordner.
  echo Bitte diese zuerst umbenennen, z. B. in C:\Temp\Komp-alt, und das Skript erneut starten.
  pause
  exit /b 1
)

if not exist "%ZIEL%" mkdir "%ZIEL%"

if exist "%ZIEL%\live" (
  echo live ist schon vorhanden, wird uebersprungen.
) else (
  git clone -b live %REPO% "%ZIEL%\live" || goto fehler
)

if exist "%ZIEL%\version-3" (
  echo version-3 ist schon vorhanden, wird uebersprungen.
) else (
  git clone -b design-v3 %REPO% "%ZIEL%\version-3" || goto fehler
)

if exist "%ZIEL%\version-4" (
  echo version-4 ist schon vorhanden, wird uebersprungen.
) else (
  git clone -b design-v4 %REPO% "%ZIEL%\version-4" || goto fehler
)
if exist "%ZIEL%\version-5" (
  echo version-5 ist schon vorhanden, wird uebersprungen.
) else (
  git clone -b design-v5 %REPO% "%ZIEL%\version-5" || goto fehler
)

echo.
echo Fertig:
echo   %ZIEL%\live       = Arbeitsversion (Branch live)
echo   %ZIEL%\version-3  = Version 3 (Branch design-v3, Archiv)
echo   %ZIEL%\version-4  = Version 4 (Branch design-v4, Archiv)
echo   %ZIEL%\version-5  = Version 5 (Branch design-v5, Archiv)
echo.
echo Naechster Schritt: In der Claude-Desktop-App eine Claude-Code-Sitzung
echo mit dem Ordner %ZIEL%\live starten.
pause
exit /b 0

:fehler
echo.
echo Beim Herunterladen ist ein Fehler aufgetreten. Bitte die Meldung oben pruefen.
pause
exit /b 1
