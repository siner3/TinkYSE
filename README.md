# **Tink – GitHub Contribution Workflow Guide**

This guide explains how to contribute to the **Tink** project repository by forking, cloning, creating branches, and submitting pull requests.

---

## **Installing Git Bash (Windows)**

If you do not have Git installed:

1. Download Git for Windows (Git Bash):
   👉 [https://git-scm.com/downloads](https://git-scm.com/downloads)

2. Click **"Windows"** → download should start automatically

3. Open the installer and follow the setup wizard

4. Keep all default settings unless you know what to change

5. After installation, open **Git Bash** from the Start Menu

To verify installation:

```bash
git --version
```

You should see something like:

```
git version 2.45.1.windows.1
```

---

## **Prerequisites**

- Git installed (Git Bash for Windows users)
- GitHub account
- Basic Git knowledge

---

## **Step 1: Fork the Repository**

1. Go to the original Tink repository on GitHub
2. Click **Fork**
3. Select your GitHub account
4. GitHub creates your personal copy

---

## **Step 2: Clone Your Fork**

```bash
git clone https://github.com/putrinaq/Tink.git
```

```bash
cd Tink
```

---

## **Step 3: Set Up Remote Repositories**

Add `upstream` (original repo):

```bash
git remote add upstream https://github.com/putrinaq/Tink.git
```

Verify:

```bash
git remote -v
```

You should see:

- `origin` → your fork
- `upstream` → original repository

---

## **Step 4: Create a New Branch**

```bash
git checkout -b your-feature-branch-name
```

Examples:

- `add-product-page-ui`
- `fix-cart-calculation`
- `update-jewelry-api-endpoints`

---

## **Step 5: Keep Your Fork Updated**

```bash
git fetch upstream
git checkout main
git merge upstream/main
git push origin main
```

---

## **Step 6: Make Your Changes**

- Implement features
- Fix bugs
- Update documentation
- Enhance UI/UX
- Test thoroughly

---

## **Step 7: Stage and Commit**

```bash
git add .
git commit -m "Add engraving customization module

- Add engraving text field
- Add backend validation
- Update product detail UI"
```

---

## **Step 8: Push Your Branch**

```bash
git push origin your-feature-branch-name
```

---

## **Step 9: Create a Pull Request**

1. Go to your fork on GitHub
2. Click **Compare & pull request**
3. Write a clear title & description
4. Link issues (e.g., `Fixes #4`)
5. Click **Create pull request**

---

## **Step 10: Respond to Feedback**

```bash
git add .
git commit -m "Address review comments"
git push origin your-feature-branch-name
```

---

# **Best Practices**

### **Branch Names**

- Short & descriptive
- One feature/fix per branch

### **Commit Messages**

- Use present tense
- Describe _what_ & _why_

### **Update Often**

```bash
git fetch upstream
git checkout main
git merge upstream/main
git push origin main
```

---

# **Common Git Commands**

```bash
git status
git log --oneline
git checkout branch
git checkout -b new-branch
git diff
git reset --soft HEAD~1
git reset --hard HEAD~1
git remote -v
git fetch upstream
git merge upstream/main
```

---

# **Troubleshooting**

### **Merge Conflicts**

1. Open conflicting files
2. Remove Git conflict markers
3. Keep final correct code
4. Commit:

```bash
git add .
git commit
```

### **If Your Fork Is Too Outdated**

```bash
git fetch upstream
git checkout main
git reset --hard upstream/main
git push origin main --force
```

⚠ This **overwrites** your main branch.
