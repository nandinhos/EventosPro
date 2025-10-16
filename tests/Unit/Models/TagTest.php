<?php

namespace Tests\Unit\Models;

use App\Models\Artist;
use App\Models\Gig;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    private Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tag = Tag::factory()->create([
            'name' => 'Rock Music',
            'slug' => 'rock-music',
            'type' => 'genre',
        ]);
    }

    #[Test]
    public function it_has_correct_fillable_attributes()
    {
        $fillable = ['name', 'slug', 'type'];

        $this->assertEquals($fillable, $this->tag->getFillable());
    }

    #[Test]
    public function it_can_be_created_with_factory()
    {
        $tag = Tag::factory()->create();

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertNotNull($tag->name);
        $this->assertNotNull($tag->slug);
        $this->assertNotNull($tag->type);
        $this->assertContains($tag->type, ['genre', 'style', 'venue', 'event']);
    }

    #[Test]
    public function it_can_be_created_with_specific_attributes()
    {
        $tag = Tag::factory()->create([
            'name' => 'Jazz Festival',
            'slug' => 'jazz-festival',
            'type' => 'event',
        ]);

        $this->assertEquals('Jazz Festival', $tag->name);
        $this->assertEquals('jazz-festival', $tag->slug);
        $this->assertEquals('event', $tag->type);
    }

    #[Test]
    public function it_has_many_gigs_relationship()
    {
        $gig1 = Gig::factory()->create();
        $gig2 = Gig::factory()->create();

        // Attach gigs to tag using the polymorphic relationship
        $this->tag->gigs()->attach([$gig1->id, $gig2->id]);

        $this->assertCount(2, $this->tag->gigs);
        $this->assertTrue($this->tag->gigs->contains($gig1));
        $this->assertTrue($this->tag->gigs->contains($gig2));
    }

    #[Test]
    public function it_has_many_artists_relationship()
    {
        $artist1 = Artist::factory()->create();
        $artist2 = Artist::factory()->create();

        // Attach artists to tag using the polymorphic relationship
        $this->tag->artists()->attach([$artist1->id, $artist2->id]);

        $this->assertCount(2, $this->tag->artists);
        $this->assertTrue($this->tag->artists->contains($artist1));
        $this->assertTrue($this->tag->artists->contains($artist2));
    }

    #[Test]
    public function it_can_have_mixed_taggable_models()
    {
        $gig = Gig::factory()->create();
        $artist = Artist::factory()->create();

        $this->tag->gigs()->attach($gig->id);
        $this->tag->artists()->attach($artist->id);

        $this->assertCount(1, $this->tag->gigs);
        $this->assertCount(1, $this->tag->artists);
        $this->assertTrue($this->tag->gigs->contains($gig));
        $this->assertTrue($this->tag->artists->contains($artist));
    }

    #[Test]
    public function it_can_detach_relationships()
    {
        $gig = Gig::factory()->create();
        $artist = Artist::factory()->create();

        // Attach first
        $this->tag->gigs()->attach($gig->id);
        $this->tag->artists()->attach($artist->id);

        $this->assertCount(1, $this->tag->gigs);
        $this->assertCount(1, $this->tag->artists);

        // Detach
        $this->tag->gigs()->detach($gig->id);
        $this->tag->artists()->detach($artist->id);

        $this->assertCount(0, $this->tag->fresh()->gigs);
        $this->assertCount(0, $this->tag->fresh()->artists);
    }

    #[Test]
    public function it_can_sync_relationships()
    {
        $gig1 = Gig::factory()->create();
        $gig2 = Gig::factory()->create();
        $gig3 = Gig::factory()->create();

        // Initial sync
        $this->tag->gigs()->sync([$gig1->id, $gig2->id]);
        $this->assertCount(2, $this->tag->gigs);

        // Sync with different gigs
        $this->tag->gigs()->sync([$gig2->id, $gig3->id]);
        $this->assertCount(2, $this->tag->fresh()->gigs);
        $this->assertFalse($this->tag->fresh()->gigs->contains($gig1));
        $this->assertTrue($this->tag->fresh()->gigs->contains($gig2));
        $this->assertTrue($this->tag->fresh()->gigs->contains($gig3));
    }

    #[Test]
    public function it_can_be_updated()
    {
        $this->tag->update([
            'name' => 'Updated Rock Music',
            'slug' => 'updated-rock-music',
            'type' => 'style',
        ]);

        $this->assertEquals('Updated Rock Music', $this->tag->fresh()->name);
        $this->assertEquals('updated-rock-music', $this->tag->fresh()->slug);
        $this->assertEquals('style', $this->tag->fresh()->type);
    }

    #[Test]
    public function it_can_be_deleted()
    {
        $tagId = $this->tag->id;

        $this->tag->delete();

        $this->assertDatabaseMissing('tags', ['id' => $tagId]);
    }
}
