;;; pp.el --- Major mode for Hoa PP grammars -*- lexical-binding: t -*-
;;
;; Copyright (C) 2015 Steven Rémot
;;
;; Author: Steven Rémot
;; Version: 0.1
;; Keywords: php, hoa
;; URL: https://github.com/stevenremot/hoa-pp-mode
;; Package-Requires: ((emacs "24.1") (names "20150723.0"))
;;
;; This program is free software: you can redistribute it and/or modify
;; it under the terms of the GNU General Public License as published by
;; the Free Software Foundation, either version 3 of the License, or
;; (at your option) any later version.
;;
;; This program is distributed in the hope that it will be useful,
;; but WITHOUT ANY WARRANTY; without even the implied warranty of
;; MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
;; GNU General Public License for more details.
;;
;; You should have received a copy of the GNU General Public License
;; along with this program.  If not, see <http://www.gnu.org/licenses/>.
;;
;;; Commentary:
;;
;; This package provides a major mode for editing hoa *.pp files.
;; It currently has syntax coloration and auto-indentation.

;;; Code:
(eval-when-compile (require 'names))

(define-namespace hoa-pp-mode-

(defconst directive-regexp (rx bol "%" (group (or "token" "skip"))
                               (? (+ blank) (group (+ (not (any blank ?\n))))
                                  (? (+ blank) (group  (+ (or (not (any ?\n ?-))
                                                              (seq ?- (not (any ?> ?\n)))))))))
  "Regular expression for matching compiler directives.")

(defconst arrow-directive-regexp (rx bol "%token"
                                     (+ (or (not (any ?\n ?-))
                                            (seq ?- (not (any ?>)))))
                                     (group "->")
                                     (? (* blank) (group (+ (not (any blank ?\n)))))
                                     eol)
  "Regular expression for matching arrow clause behind comiler directives.")

(defconst rule-regexp (rx (? "#") (group (+ (or word (syntax symbol)))) ":" (* space) eol)
  "Regular expression for matching rule declaration.")

(defconst font-lock-keywords
  `(;; Compiler directives
    (,hoa-pp-mode-directive-regexp . (1 font-lock-keyword-face))
    (,hoa-pp-mode-directive-regexp . (2 font-lock-constant-face))
    (,hoa-pp-mode-directive-regexp . (3 font-lock-string-face))
    (,hoa-pp-mode-arrow-directive-regexp . (1 font-lock-builtin-face))
    (,hoa-pp-mode-arrow-directive-regexp . (2 font-lock-constant-face))

    ;; Rules
    (,(rx bol (* space) (? "#") (group (+ (or word (syntax symbol)))) ":" (* space) eol)  . (1 font-lock-function-name-face))

    ;; Token and rule use
    (, (rx "::" (group (+ (or word (syntax symbol)))) "::") . (1 font-lock-constant-face))
    (, (rx "<" (group (+ (or word (syntax symbol)))) ">") . (1 font-lock-constant-face))
    (, (rx bow (group (+ (or word (syntax symbol)))) "()") . (1 font-lock-function-name-face))
    )
  "Keywords highlighting for Hoa PP mode.")

(defun setup-syntax-table (&optional table)
  "Setup the syntax table for `hoa-pp-mode'.
TABLE is a syntax table.  It will be the default table if not provided."
  (modify-syntax-entry ?\" "." table)
  (modify-syntax-entry ?/ "<12" table)
  (modify-syntax-entry ?\n ">" table))

(defun is-at-directive-start? ()
  "Return t if the current point is at a directive start."
  (= ?% (aref (buffer-substring-no-properties (point) (1+ (point))) 0)))

(defun is-at-rule-start? ()
  "Return t if the current point is at a rule start."
  (looking-at rule-regexp))

(defun is-in-rule? ()
  "Return t if the current point is inside a rule definition."
  (catch 'in-rule?
    (save-excursion
      (forward-line -1)
      (beginning-of-line)
      (while (not (= (point) (point-min)))
        (back-to-indentation)
        (when (is-at-directive-start?)
          (throw 'in-rule? nil))
        (when (is-at-rule-start?)
          (throw 'in-rule? t))
        (forward-line -1)
        (beginning-of-line)))))

(defun ensure-in-text ()
  "Go back to indentation of point is before line's text."
  (let ((text-start (save-excursion
                      (back-to-indentation)
                      (point))))
    (when (< (point) text-start)
      (back-to-indentation))))

(defun indent-line ()
  "Indent the current line."
  (interactive)
  (save-excursion
    (back-to-indentation)
    (cond
     ((is-at-directive-start?)
      (beginning-of-line)
      (just-one-space 0))
     ((is-at-rule-start?)
      (beginning-of-line)
      (just-one-space 0))
     ((is-in-rule?)
      (indent-to (indent-next-tab-stop 0)))
     (t
      (indent-to 0))))
  (ensure-in-text))

(defun setup-indentation ()
  "Setup the indent function for `hoa-pp-mode'."
  (set (make-local-variable 'indent-line-function) #'indent-line)
  (set (make-local-variable 'electric-indent-chars) '(?\n ?:)))
)

;;;###autoload
(define-derived-mode hoa-pp-mode
  prog-mode "Hoa PP"
  "Major mode for editing Hoa PP grammars.
\\{hoa-pp-mode-map"
  (set (make-local-variable 'font-lock-defaults) '(hoa-pp-mode-font-lock-keywords))
  (hoa-pp-mode-setup-syntax-table)
  (hoa-pp-mode-setup-indentation))

;;;###autoload
(add-to-list 'auto-mode-alist '("\\.pp$" . hoa-pp-mode))

(provide 'hoa-pp-mode)

;;; pp.el ends here
