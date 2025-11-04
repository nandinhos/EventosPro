# Token Optimization Strategies

> **Purpose**: Maximize effectiveness, minimize token consumption through smart context reuse and precise communication.

## 🎯 Core Principle

**More specific context = Better answers with fewer tokens**

## 📊 Token Cost Hierarchy

### High Cost (Avoid when possible)
- ❌ Reading entire service files "just in case"
- ❌ Asking general questions without context
- ❌ Re-explaining patterns already documented
- ❌ Reading full documentation when summary exists

### Medium Cost (Use strategically)
- ⚠️ Reading specific methods from services
- ⚠️ Reading test files for patterns
- ⚠️ Searching docs with `search-docs` tool

### Low Cost (Use freely)
- ✅ Referencing line numbers from already-read files
- ✅ Using Quick Reference sections
- ✅ Asking with context from guides
- ✅ Pointing to specific errors

## 🔄 Context Reuse Patterns

### Pattern 1: Reference Previous Reads

**❌ Token-Wasting:**
```
User: "I need to add a financial calculation"
AI: *Reads GigFinancialCalculatorService (2000 tokens)*
AI: *Reads AuditService (1500 tokens)*
AI: *Reads ExchangeRateService (1200 tokens)*
```

**✅ Token-Efficient:**
```
User: "Following Services Guide 3.1, I need to add commission recalculation similar to GigFinancialCalculatorService::calculateBookerCommissionBrl. Should I add a new method to existing service or create CommissionRecalculationService?"
AI: *Uses existing knowledge from guides, answers directly*
```

**Token Savings**: ~4000 tokens

---

### Pattern 2: Precise Error Context

**❌ Token-Wasting:**
```
User: "My test is failing"
AI: "Can you share the test code?"
User: *Shares entire 200-line test file*
AI: *Reads entire file*
AI: "What's the error message?"
User: *Shares error*
```

**✅ Token-Efficient:**
```
User: "Test failing at AuditServiceTest.php:45

Error: Failed asserting that 500.0 is of type string

Line 45: $this->assertIsFloat($payment->due_value);

Payment factory creates due_value=500.00. Per Common Pitfalls guide section 3, should I use assertIsString?"

AI: "Yes, exactly. Laravel decimal cast returns strings..."
```

**Token Savings**: ~1500 tokens + fewer back-and-forth messages

---

### Pattern 3: Guide-Based Questions

**❌ Token-Wasting:**
```
User: "How do I test a Filament resource?"
AI: *Reads Testing Standards guide (3000 tokens)*
AI: *Reads Filament documentation via search-docs (2000 tokens)*
AI: *Provides generic answer*
```

**✅ Token-Efficient:**
```
User: "Testing Standards guide section 4.2 shows Livewire testing. I need to test GigResource table can filter by date range. Should I follow the same pattern with ->searchTable() or use ->filterTable()?"
AI: "Use ->filterTable() for Filament filters..."
```

**Token Savings**: ~4500 tokens

---

### Pattern 4: File Reference Strategy

**❌ Token-Wasting:**
```
User: "I need to create a service similar to AuditService"
AI: *Reads entire AuditService.php (2500 tokens)*
AI: *Reads entire GigFinancialCalculatorService.php (2000 tokens)*
```

**✅ Token-Efficient:**
```
User: "I need to create BookerReportService. Should I follow the structure from AuditService.php:15-30 (constructor injection + private methods) or GigFinancialCalculatorService.php:20-35 (public methods + optimized queries)?"
AI: "Since it's for reporting, follow AuditService pattern..."
```

**Token Savings**: ~3000 tokens (AI already knows these patterns from guides)

---

## 📖 When to Read Full Files vs Guides

### Read Full File When:
- ✅ Implementing a closely related feature
- ✅ Fixing a bug in that specific file
- ✅ Extending an existing class
- ✅ Creating a test for that specific file

### Read Guide Instead When:
- ✅ Learning general patterns
- ✅ Understanding architecture
- ✅ Following conventions
- ✅ Quick reference for commands

### Example Decision Tree:

```
Need to add new financial calculation?
  │
  ├─ Similar to existing method in GigFinancialCalculatorService?
  │   └─> Read specific method range (e.g., lines 45-80)
  │
  ├─ New calculation logic entirely?
  │   └─> Read Services Guide (section 3), then ask with context
  │
  └─ Just need to understand flow?
      └─> Read Financial Rules Guide (section 1)
```

---

## 🎯 Effective Communication Templates

### Template 1: New Feature

```
Task: [Brief description]

Context Read:
- [Guide Name] section [X]
- [File Name] lines [X-Y] (if applicable)

Question:
[Specific question with 2-3 options if possible]

Example:
Task: Add booker sales report with date range filter

Context Read:
- Services Guide section 3.4 (BookerFinancialsService)
- Financial Rules Guide section 2 (calculation flow)

Question:
Should I extend BookerFinancialsService with getFilteredSalesReport(array $filters) method, or create new BookerReportService? Concern: BookerFinancialsService already has 8 methods.
```

### Template 2: Bug Fix

```
Error: [Exact error message]

Location: [File:Line]

Code Snippet: [Minimal relevant code]

What I Checked:
- [Common Pitfall X] - not applicable
- [Guide Y section Z] - might be related

Question: [Specific fix question]

Example:
Error: Call to undefined method calculateNetPayoutUsd()

Location: GigController.php:145

Code Snippet:
$netPayout = $this->calculator->calculateNetPayoutUsd($gig);

What I Checked:
- Services Guide 3.1 - GigFinancialCalculatorService only has BRL methods
- Financial Rules Guide 2.1 - all calculations should be in BRL

Question: Should I convert result after calling calculateArtistNetPayout() with ExchangeRateService, or add USD-specific method to calculator service?
```

### Template 3: Test Writing

```
Testing: [What you're testing]

Reference:
- Testing Standards section [X]
- Similar test: [File:Line] (if exists)

Setup: [Minimal factory/mock setup]

Question: [Specific assertion/pattern question]

Example:
Testing: FinancialProjectionService::getProjectedCashFlow()

Reference:
- Testing Standards section 4.3 (service testing)
- Similar test: AuditServiceTest.php:120-145

Setup:
$gigs = Gig::factory()->count(3)->create(['gig_date' => now()->addDays(30)]);

Question: Should I mock ExchangeRateService or use Config::set for exchange rates? Test needs predictable currency conversion.
```

---

## 🚀 Quick Decision Matrix

| Scenario | Token-Efficient Approach | Tokens Saved |
|----------|-------------------------|--------------|
| Need command syntax | Use Quick Reference in CLAUDE.md | ~500 |
| Understanding flow | Read relevant Guide section | ~2000 |
| Similar feature exists | Reference specific lines, not full file | ~1500 |
| Error debugging | Share error + line + context | ~1000 |
| Test patterns | Reference Testing Standards + similar test | ~1500 |
| Configuration question | Mention Common Pitfalls section 7 | ~800 |

**Average conversation savings**: 3000-5000 tokens per task

---

## 📋 Before Asking Checklist

Before posting a question, verify:

- [ ] Checked relevant Guide section
- [ ] Checked Common Pitfalls guide for similar issue
- [ ] Checked Quick Reference for command syntax
- [ ] Can reference specific line numbers if file already discussed
- [ ] Have specific question (not "how do I do X?")
- [ ] Can provide 2-3 potential approaches for validation

---

## 🎓 Learning Curve Strategy

### Week 1: Learn the Guides
- Read all guides once
- Reference them heavily
- Ask questions with guide context

### Week 2-3: Build Mental Model
- Start recognizing patterns
- Reference specific sections
- Fewer guide reads needed

### Week 4+: Autonomous Work
- Use Quick Reference mostly
- Read guides only for complex features
- Ask targeted questions with line references

---

## 📊 Token Consumption Examples

### Inefficient Conversation (12,000 tokens)
```
User: "I need to add a feature"
AI: *Reads architecture docs (3000 tokens)*
User: "It's a financial calculation"
AI: *Reads financial services (4000 tokens)*
User: "Actually for booker commissions"
AI: *Reads BookerFinancialsService (2000 tokens)*
AI: *Provides answer (1000 tokens)*
User: "How do I test it?"
AI: *Reads testing guide (2000 tokens)*
```

### Efficient Conversation (3,000 tokens)
```
User: "Need to add booker commission override feature. Services Guide 3.4 shows BookerFinancialsService. Should I add calculateOverrideCommission() method there or extend GigFinancialCalculatorService? Override applies to specific gigs. Also, Testing Standards 4.2 pattern for testing?"

AI: "Add to GigFinancialCalculatorService since it's gig-specific. Method signature... For testing, follow pattern from GigFinancialCalculatorServiceTest.php:89-105..."
(1500 tokens)

User: "Perfect. One more: should override value be stored in Gig model or separate CommissionOverride model?"

AI: "Add override_commission nullable decimal field to Gig model. Simpler, fewer joins..."
(500 tokens)
```

**Token Savings**: 75% (9,000 tokens saved)

---

## 🔧 Tools for Token Efficiency

### Use Laravel Boost Tools

```bash
# Instead of asking "what parameters does migrate take?"
list-artisan-commands

# Instead of asking "what's in the database?"
database-query

# Instead of asking "does this code work?"
tinker

# Instead of reading full Laravel docs
search-docs ["query builder", "eloquent relationships"]
```

### Use Grep Strategically

```bash
# Find where a service is used
sail bash -c "grep -r 'GigFinancialCalculatorService' app/"

# Find similar test patterns
sail bash -c "grep -r 'assertIsString' tests/"
```

---

## 💡 Pro Tips

1. **Bookmark Common References**
   - Keep CLAUDE.md Quick Reference open
   - Note which guides you reference most
   - Create personal notes mapping tasks → guide sections

2. **Build Context Library**
   - After solving a problem, note the pattern
   - Reference your own solutions: "Like I did in GigController.php:145"
   - AI remembers context within conversation

3. **Batch Questions**
   - Instead of sequential questions, ask related ones together
   - Example: "I need to add X feature. Question 1: [architecture]. Question 2: [testing]. Question 3: [validation]."

4. **Use Code Comments**
   - Add `// Per Services Guide 3.1` in your code
   - Helps future AI conversations understand your intent
   - Saves re-explaining decisions

---

**Last Updated**: 2025-10-27
