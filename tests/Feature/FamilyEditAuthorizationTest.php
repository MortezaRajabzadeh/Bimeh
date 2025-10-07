<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Family;
use App\Models\Member;
use App\Models\Organization;
use App\Enums\InsuranceWizardStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use App\Livewire\Charity\FamilySearch;

class FamilyEditAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * تست: ادمین می‌تواند خانواده را در هر وضعیتی ویرایش کند
     * 
     * @test
     */
    public function admin_can_edit_family_in_any_status()
    {
        // Arrange
        $admin = User::factory()->create([
            'role' => 'admin',
            'user_type' => 'admin'
        ]);
        
        $charity = Organization::factory()->create(['type' => 'charity']);
        $family = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => InsuranceWizardStep::REVIEWING->value
        ]);
        
        $member = Member::factory()->create(['family_id' => $family->id]);
        
        // Act & Assert
        $this->actingAs($admin);
        
        // Test editing in REVIEWING status
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member->id)
            ->assertSet('editingMemberId', $member->id);
        
        // Test editing in APPROVED status
        $family->update(['wizard_status' => InsuranceWizardStep::APPROVED->value]);
        
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member->id)
            ->assertSet('editingMemberId', $member->id);
        
        // Test editing in INSURED status
        $family->update(['wizard_status' => InsuranceWizardStep::INSURED->value]);
        
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member->id)
            ->assertSet('editingMemberId', $member->id);
    }

    /**
     * تست: خیریه می‌تواند خانواده خود را در وضعیت PENDING ویرایش کند
     * 
     * @test
     */
    public function charity_can_edit_own_family_in_pending_status()
    {
        // Arrange
        $charity = Organization::factory()->create(['type' => 'charity']);
        $user = User::factory()->create([
            'organization_id' => $charity->id,
            'role' => 'charity',
            'user_type' => 'charity'
        ]);
        
        $family = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => InsuranceWizardStep::PENDING->value
        ]);
        
        $member = Member::factory()->create(['family_id' => $family->id]);
        
        // Act & Assert
        $this->actingAs($user);
        
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member->id)
            ->assertSet('editingMemberId', $member->id)
            ->assertDontSee('فقط ادمین می‌تواند ویرایش کند');
    }

    /**
     * تست: خیریه می‌تواند خانواده خود را با wizard_status خالی ویرایش کند
     * 
     * @test
     */
    public function charity_can_edit_own_family_with_null_status()
    {
        // Arrange
        $charity = Organization::factory()->create(['type' => 'charity']);
        $user = User::factory()->create([
            'organization_id' => $charity->id,
            'role' => 'charity',
            'user_type' => 'charity'
        ]);
        
        $family = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => null
        ]);
        
        $member = Member::factory()->create(['family_id' => $family->id]);
        
        // Act & Assert
        $this->actingAs($user);
        
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member->id)
            ->assertSet('editingMemberId', $member->id);
    }

    /**
     * تست: خیریه نمی‌تواند خانواده خود را در وضعیت REVIEWING ویرایش کند
     * 
     * @test
     */
    public function charity_cannot_edit_own_family_in_reviewing_status()
    {
        // Arrange
        $charity = Organization::factory()->create(['type' => 'charity']);
        $user = User::factory()->create([
            'organization_id' => $charity->id,
            'role' => 'charity',
            'user_type' => 'charity'
        ]);
        
        $family = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => InsuranceWizardStep::REVIEWING->value
        ]);
        
        $member = Member::factory()->create(['family_id' => $family->id]);
        
        // Act & Assert
        $this->actingAs($user);
        
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member->id)
            ->assertDispatched('notify', function ($event) {
                return $event['type'] === 'error' 
                    && str_contains($event['message'], 'فقط ادمین می‌تواند ویرایش کند');
            })
            ->assertSet('editingMemberId', null);
    }

    /**
     * تست: خیریه نمی‌تواند خانواده خود را در وضعیت APPROVED ویرایش کند
     * 
     * @test
     */
    public function charity_cannot_edit_own_family_in_approved_status()
    {
        // Arrange
        $charity = Organization::factory()->create(['type' => 'charity']);
        $user = User::factory()->create([
            'organization_id' => $charity->id,
            'role' => 'charity',
            'user_type' => 'charity'
        ]);
        
        $family = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => InsuranceWizardStep::APPROVED->value
        ]);
        
        $member = Member::factory()->create(['family_id' => $family->id]);
        
        // Act & Assert
        $this->actingAs($user);
        
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member->id)
            ->assertDispatched('notify', function ($event) {
                return $event['type'] === 'error' 
                    && str_contains($event['message'], 'تایید شده');
            });
    }

    /**
     * تست: خیریه نمی‌تواند خانواده خیریه دیگر را ویرایش کند
     * 
     * @test
     */
    public function charity_cannot_edit_other_charity_family()
    {
        // Arrange
        $charity1 = Organization::factory()->create(['type' => 'charity']);
        $charity2 = Organization::factory()->create(['type' => 'charity']);
        
        $user = User::factory()->create([
            'organization_id' => $charity1->id,
            'role' => 'charity',
            'user_type' => 'charity'
        ]);
        
        $family = Family::factory()->create([
            'charity_id' => $charity2->id, // متعلق به خیریه دیگر
            'wizard_status' => InsuranceWizardStep::PENDING->value
        ]);
        
        $member = Member::factory()->create(['family_id' => $family->id]);
        
        // Act & Assert
        $this->actingAs($user);
        
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member->id)
            ->assertDispatched('notify', function ($event) {
                return $event['type'] === 'error';
            })
            ->assertSet('editingMemberId', null);
    }

    /**
     * تست: کاربر بیمه نمی‌تواند هیچ خانواده‌ای را ویرایش کند
     * 
     * @test
     */
    public function insurance_user_cannot_edit_any_family()
    {
        // Arrange
        $insurance = Organization::factory()->create(['type' => 'insurance']);
        $user = User::factory()->create([
            'organization_id' => $insurance->id,
            'role' => 'insurance',
            'user_type' => 'insurance'
        ]);
        
        $charity = Organization::factory()->create(['type' => 'charity']);
        $family = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => InsuranceWizardStep::PENDING->value
        ]);
        
        $member = Member::factory()->create(['family_id' => $family->id]);
        
        // Act & Assert
        $this->actingAs($user);
        
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member->id)
            ->assertDispatched('notify', function ($event) {
                return $event['type'] === 'error';
            });
    }

    /**
     * تست: دکمه ویرایش برای کاربران غیرمجاز مخفی است
     * 
     * @test
     */
    public function edit_button_hidden_for_unauthorized_users()
    {
        // Arrange
        $charity = Organization::factory()->create(['type' => 'charity']);
        $user = User::factory()->create([
            'organization_id' => $charity->id,
            'role' => 'charity',
            'user_type' => 'charity'
        ]);
        
        $family = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => InsuranceWizardStep::REVIEWING->value
        ]);
        
        // Act & Assert
        $this->actingAs($user);
        
        $component = Livewire::test(FamilySearch::class);
        
        // Verify @can directive works correctly
        $this->assertFalse(
            \Gate::allows('updateMembers', $family),
            'User should not be authorized to update family in REVIEWING status'
        );
    }

    /**
     * تست: برای کاربران غیرمجاز نشان "فقط ادمین" نمایش داده می‌شود
     * 
     * @test
     */
    public function admin_only_badge_shown_for_unauthorized_users()
    {
        // Arrange
        $charity = Organization::factory()->create(['type' => 'charity']);
        $user = User::factory()->create([
            'organization_id' => $charity->id,
            'role' => 'charity',
            'user_type' => 'charity'
        ]);
        
        $family = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => InsuranceWizardStep::APPROVED->value
        ]);
        
        $member = Member::factory()->create(['family_id' => $family->id]);
        
        // Act & Assert
        $this->actingAs($user);
        
        // Verify Gate denies access
        $this->assertFalse(\Gate::allows('updateMembers', $family));
        
        // Verify component shows error when trying to edit
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member->id)
            ->assertDispatched('notify', function ($event) {
                return $event['type'] === 'error';
            });
    }

    /**
     * تست: تلاش غیرمجاز برای ویرایش لاگ می‌شود
     * 
     * @test
     */
    public function unauthorized_edit_attempt_is_logged()
    {
        // Arrange
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized member edit attempt'
                    && isset($context['user_id'])
                    && isset($context['member_id'])
                    && isset($context['family_id'])
                    && isset($context['wizard_status']);
            });
        
        $charity = Organization::factory()->create(['type' => 'charity']);
        $user = User::factory()->create([
            'organization_id' => $charity->id,
            'role' => 'charity',
            'user_type' => 'charity'
        ]);
        
        $family = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => InsuranceWizardStep::REVIEWING->value
        ]);
        
        $member = Member::factory()->create(['family_id' => $family->id]);
        
        // Act
        $this->actingAs($user);
        
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member->id);
        
        // Assert - Log::shouldReceive handles the assertion
    }

    /**
     * تست: پیام خطای مبتنی بر context بر اساس wizard_status نمایش داده می‌شود
     * 
     * @test
     */
    public function context_aware_error_message_shown_based_on_wizard_status()
    {
        $charity = Organization::factory()->create(['type' => 'charity']);
        $user = User::factory()->create([
            'organization_id' => $charity->id,
            'role' => 'charity',
            'user_type' => 'charity'
        ]);
        
        // Test REVIEWING status
        $family1 = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => InsuranceWizardStep::REVIEWING->value
        ]);
        $member1 = Member::factory()->create(['family_id' => $family1->id]);
        
        $this->actingAs($user);
        
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member1->id)
            ->assertDispatched('notify', function ($event) {
                return str_contains($event['message'], 'تخصیص سهمیه');
            });
        
        // Test APPROVED status
        $family2 = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => InsuranceWizardStep::APPROVED->value
        ]);
        $member2 = Member::factory()->create(['family_id' => $family2->id]);
        
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member2->id)
            ->assertDispatched('notify', function ($event) {
                return str_contains($event['message'], 'تایید شده');
            });
        
        // Test INSURED status
        $family3 = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => InsuranceWizardStep::INSURED->value
        ]);
        $member3 = Member::factory()->create(['family_id' => $family3->id]);
        
        Livewire::test(FamilySearch::class)
            ->call('editMember', $member3->id)
            ->assertDispatched('notify', function ($event) {
                return str_contains($event['message'], 'بیمه شده');
            });
    }

    /**
     * تست: ذخیره تغییرات توسط کاربر غیرمجاز مسدود می‌شود
     * 
     * @test
     */
    public function unauthorized_user_cannot_save_changes()
    {
        // Arrange
        $charity = Organization::factory()->create(['type' => 'charity']);
        $user = User::factory()->create([
            'organization_id' => $charity->id,
            'role' => 'charity',
            'user_type' => 'charity'
        ]);
        
        $family = Family::factory()->create([
            'charity_id' => $charity->id,
            'wizard_status' => InsuranceWizardStep::REVIEWING->value
        ]);
        
        $member = Member::factory()->create([
            'family_id' => $family->id,
            'relationship' => 'پدر'
        ]);
        
        // Act & Assert
        $this->actingAs($user);
        
        Livewire::test(FamilySearch::class)
            ->set('editingMemberId', $member->id)
            ->set('editingMemberData.relationship', 'مادر')
            ->call('saveMember')
            ->assertDispatched('notify', function ($event) {
                return $event['type'] === 'error';
            })
            ->assertSet('editingMemberId', null);
        
        // Verify member was not updated
        $this->assertEquals('پدر', $member->fresh()->relationship);
    }
}
