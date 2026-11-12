import 'package:flutter_test/flutter_test.dart';
import 'package:ai_commerce_mobile/features/auth/data/models/user_model.dart';
import 'package:ai_commerce_mobile/features/auth/data/models/auth_response_model.dart';
import 'package:ai_commerce_mobile/features/products/data/models/canonical_product_model.dart';
import 'package:ai_commerce_mobile/features/products/data/models/product_listing_model.dart';
import 'package:ai_commerce_mobile/features/market_research/data/models/market_item_model.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/data/models/ai_analysis_model.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/data/models/financial_metrics_model.dart';

void main() {
  group('Data Models Tests', () {
    test('UserModel.fromJson parses user fields correctly', () {
      final json = {
        'id': 1,
        'name': 'Test User',
        'email': 'test@example.com',
        'created_at': '2026-11-06T12:00:00Z',
      };

      final user = UserModel.fromJson(json);
      expect(user.id, 1);
      expect(user.name, 'Test User');
      expect(user.email, 'test@example.com');
    });

    test('AuthResponseModel.fromJson parses both wrapped and direct payloads', () {
      final wrappedJson = {
        'success': true,
        'message': 'Logged in successfully',
        'data': {
          'user': {'id': 1, 'name': 'Demo User', 'email': 'demo@example.com'},
          'token': '123|abcdef',
          'token_type': 'Bearer',
        },
      };

      final auth1 = AuthResponseModel.fromJson(wrappedJson);
      expect(auth1.user.id, 1);
      expect(auth1.user.name, 'Demo User');
      expect(auth1.token, '123|abcdef');
      expect(auth1.tokenType, 'Bearer');

      final directJson = {
        'user': {'id': 2, 'name': 'Direct User', 'email': 'direct@example.com'},
        'token': '456|ghijkl',
        'token_type': 'Bearer',
      };

      final auth2 = AuthResponseModel.fromJson(directJson);
      expect(auth2.user.id, 2);
      expect(auth2.token, '456|ghijkl');
    });

    test('CanonicalProductModel.fromJson parses real Laravel canonical payload correctly', () {
      final json = {
        'id': 10,
        'name': 'Anker Wireless Charger',
        'slug': 'anker-wireless-charger',
        'brand': 'Anker',
        'model_number': 'ANK-1234',
        'description': 'Fast wireless charging pad.',
        'base_currency': 'USD',
        'category': {
          'id': 2,
          'name': 'Electronics',
          'slug': 'electronics',
          'description': null,
          'parent_id': null,
        },
        'price_summary': {
          'lowest_price': '15.9900',
          'highest_price': '29.9900',
          'average_price': '22.9900',
          'currency': 'USD',
        },
        'active_listings_count': 3,
        'total_listings_count': 4,
        'rating': 4.7,
        'reviews_count': 520,
        'source': {
          'id': 1,
          'code': 'ebay',
          'name': 'eBay Marketplace',
          'base_url': 'https://www.ebay.com',
          'is_active': true,
        },
        'external_id': 'ebay_123',
        'latest_opportunity_score': 85.5,
        'latest_metric': {
          'id': 11,
          'opportunity_score': '85.50',
          'risk_score': '20.00',
        },
        'listings': [],
        'created_at': '2026-11-10T10:00:00.000000Z',
        'updated_at': '2026-11-10T10:00:00.000000Z',
      };

      final product = CanonicalProductModel.fromJson(json);
      expect(product.id, 10);
      expect(product.name, 'Anker Wireless Charger');
      expect(product.brand, 'Anker');
      expect(product.categoryName, 'Electronics');
      expect(product.lowestPrice, 15.99);
      expect(product.highestPrice, 29.99);
      expect(product.averagePrice, 22.99);
      expect(product.priceSummary.currency, 'USD');
      expect(product.activeListingsCount, 3);
      expect(product.totalListingsCount, 4);
      expect(product.latestOpportunityScore, 85.5);
    });

    test('ProductListingModel.fromJson parses canonical listing and safe seller signals without raw_payload', () {
      final json = {
        'id': 101,
        'canonical_product_id': 10,
        'external_id': 'ebay_123',
        'title': 'Anker Qi Fast Charger - eBay Offer',
        'source': {
          'id': 1,
          'code': 'ebay',
          'name': 'eBay Marketplace',
          'base_url': 'https://www.ebay.com',
          'is_active': true,
        },
        'category': null,
        'brand': 'Anker',
        'price': '18.5000',
        'original_price': '24.9900',
        'currency': 'USD',
        'rating': '4.80',
        'reviews_count': 240,
        'is_available': true,
        'url': 'https://www.ebay.com/itm/123',
        'image_url': 'https://i.ebayimg.com/sample.jpg',
        'observed_at': '2026-11-10T10:00:00.000000Z',
        'seller': {
          'username': 'TechStoreUSA',
          'store_name': null,
          'feedback_percentage': 99.4,
          'feedback_score': 4200,
        },
        'created_at': '2026-11-10T10:00:00.000000Z',
        'updated_at': '2026-11-10T10:00:00.000000Z',
      };

      final listing = ProductListingModel.fromJson(json);
      expect(listing.id, 101);
      expect(listing.canonicalProductId, 10);
      expect(listing.title, 'Anker Qi Fast Charger - eBay Offer');
      expect(listing.sourceCode, 'ebay');
      expect(listing.sourceName, 'eBay Marketplace');
      expect(listing.price, 18.50);
      expect(listing.currency, 'USD');
      expect(listing.seller, isNotNull);
      expect(listing.seller!.username, 'TechStoreUSA');
      expect(listing.seller!.displayName, 'TechStoreUSA');
      expect(listing.seller!.feedbackPercentage, 99.4);
      expect(listing.seller!.feedbackScore, 4200);
      expect(listing.isAvailable, true);
    });

    test('MarketItemModel.fromJson parses market research item correctly', () {
      final json = {
        'id': 1,
        'external_id': 'v1|123456789|0',
        'title': 'Apple AirPods Pro 2 USB-C',
        'price': 189.99,
        'currency': 'USD',
        'rating': 4.8,
        'reviews_count': 350,
        'is_available': true,
        'url': 'https://www.ebay.com/itm/123456789',
        'source': {
          'name': 'eBay Marketplace',
          'code': 'ebay',
        },
      };

      final item = MarketItemModel.fromJson(json);
      expect(item.id, 1);
      expect(item.externalId, 'v1|123456789|0');
      expect(item.title, 'Apple AirPods Pro 2 USB-C');
      expect(item.price, 189.99);
      expect(item.currency, 'USD');
      expect(item.rating, 4.8);
      expect(item.reviewsCount, 350);
      expect(item.isAvailable, true);
      expect(item.sourceCode, 'ebay');
    });

    test('FinancialMetricsModel.fromJson parses deterministic data truth', () {
      final json = {
        'purchase_price': 120.00,
        'selling_price': 199.99,
        'shipping_cost': {'value': 15.00, 'is_verified': true},
        'estimated_fees': 20.00,
        'other_costs': 0.00,
        'total_cost': 155.00,
        'estimated_profit': {'value': 44.99, 'status': 'CALCULATED_CONFIRMED'},
        'profit_margin_percent': 22.50,
        'return_on_investment_percent': 29.03,
        'is_profitable': true,
      };

      final metrics = FinancialMetricsModel.fromJson(json);
      expect(metrics.purchasePrice, 120.00);
      expect(metrics.sellingPrice, 199.99);
      expect(metrics.shippingCost, 15.00);
      expect(metrics.totalCost, 155.00);
      expect(metrics.estimatedProfit, 44.99);
      expect(metrics.profitMarginPercent, 22.5);
      expect(metrics.roiPercent, 29.03);
      expect(metrics.isProfitable, true);
      expect(metrics.isShippingVerified, true);
      expect(metrics.profitStatus, 'CALCULATED_CONFIRMED');
    });

    test('FinancialMetricsModel Case A: unavailable financial data preserves null and status', () {
      final metrics = FinancialMetricsModel.fromJson({
        'purchase_price': {'value': '599.9900', 'truth': 'OBSERVED'},
        'selling_price': {'value': null, 'truth': null, 'status': 'DATA_UNAVAILABLE'},
        'shipping_cost': {'value': '0.0000', 'truth': 'ESTIMATED', 'is_verified': false},
        'estimated_fees': {'value': '0.0000', 'truth': 'ESTIMATED'},
        'other_costs': {'value': '0.0000', 'truth': 'ESTIMATED'},
        'total_cost': {'value': '599.9900', 'truth': 'CALCULATED'},
        'estimated_profit': {'value': null, 'truth': null, 'status': 'INSUFFICIENT_DATA'},
        'profit_margin_percent': {'value': null, 'truth': null},
        'return_on_investment_percent': {'value': null, 'truth': null},
        'is_profitable': {'value': null, 'truth': null},
      });

      expect(metrics.purchasePrice, 599.99);
      expect(metrics.sellingPrice, isNull);
      expect(metrics.sellingPrice, isNot(0));
      expect(metrics.estimatedProfit, isNull);
      expect(metrics.estimatedProfit, isNot(0));
      expect(metrics.profitMarginPercent, isNull);
      expect(metrics.roiPercent, isNull);
      expect(metrics.isProfitable, isNull);
      expect(metrics.isProfitable, isNot(true));
      expect(metrics.profitStatus, 'INSUFFICIENT_DATA');

      // Also preserves DATA_UNAVAILABLE when estimated_profit omits status
      final dataUnavailableOnly = FinancialMetricsModel.fromJson({
        'selling_price': {'value': null, 'truth': null, 'status': 'DATA_UNAVAILABLE'},
        'estimated_profit': {'value': null, 'truth': null},
        'profit_margin_percent': {'value': null, 'truth': null},
        'return_on_investment_percent': {'value': null, 'truth': null},
        'is_profitable': {'value': null, 'truth': null},
      });
      expect(dataUnavailableOnly.sellingPrice, isNull);
      expect(dataUnavailableOnly.estimatedProfit, isNull);
      expect(dataUnavailableOnly.isProfitable, isNull);
      expect(dataUnavailableOnly.profitStatus, 'DATA_UNAVAILABLE');
    });

    test('FinancialMetricsModel Case B: actual zero parses to 0.0 distinctly from null', () {
      final zeroMetrics = FinancialMetricsModel.fromJson({
        'purchase_price': {'value': 0, 'truth': 'OBSERVED'},
        'selling_price': {'value': 0, 'truth': 'CALCULATED'},
        'shipping_cost': {'value': 0, 'truth': 'ESTIMATED', 'is_verified': true},
        'estimated_fees': {'value': 0, 'truth': 'ESTIMATED'},
        'other_costs': {'value': 0, 'truth': 'ESTIMATED'},
        'total_cost': {'value': 0, 'truth': 'CALCULATED'},
        'estimated_profit': {'value': 0, 'truth': 'CALCULATED', 'status': 'CALCULATED_CONFIRMED'},
        'profit_margin_percent': {'value': 0, 'truth': 'CALCULATED'},
        'return_on_investment_percent': {'value': 0, 'truth': 'CALCULATED'},
        'is_profitable': {'value': false, 'truth': 'CALCULATED'},
      });

      expect(zeroMetrics.purchasePrice, 0.0);
      expect(zeroMetrics.purchasePrice, isNotNull);
      expect(zeroMetrics.sellingPrice, 0.0);
      expect(zeroMetrics.sellingPrice, isNotNull);
      expect(zeroMetrics.estimatedProfit, 0.0);
      expect(zeroMetrics.estimatedProfit, isNotNull);
      expect(zeroMetrics.profitMarginPercent, 0.0);
      expect(zeroMetrics.profitMarginPercent, isNotNull);
      expect(zeroMetrics.roiPercent, 0.0);
      expect(zeroMetrics.roiPercent, isNotNull);
      expect(zeroMetrics.isProfitable, false);
      expect(zeroMetrics.isProfitable, isNotNull);
    });

    test('FinancialMetricsModel Case C: normal explicit financial data preserves calculated values', () {
      final metrics = FinancialMetricsModel.fromJson({
        'purchase_price': {'value': '100.0000', 'truth': 'ESTIMATED'},
        'selling_price': {'value': '150.0000', 'truth': 'CALCULATED'},
        'shipping_cost': {'value': '0.0000', 'truth': 'ESTIMATED', 'is_verified': true},
        'estimated_fees': {'value': '0.0000', 'truth': 'ESTIMATED'},
        'other_costs': {'value': '0.0000', 'truth': 'ESTIMATED'},
        'total_cost': {'value': '100.0000', 'truth': 'CALCULATED'},
        'estimated_profit': {'value': '50.0000', 'truth': 'CALCULATED', 'status': 'CALCULATED_CONFIRMED'},
        'profit_margin_percent': {'value': 33.3333, 'truth': 'CALCULATED'},
        'return_on_investment_percent': {'value': 50, 'truth': 'CALCULATED'},
        'is_profitable': {'value': true, 'truth': 'CALCULATED'},
      });

      expect(metrics.purchasePrice, 100.0);
      expect(metrics.sellingPrice, 150.0);
      expect(metrics.estimatedProfit, 50.0);
      expect(metrics.profitMarginPercent, 33.3333);
      expect(metrics.roiPercent, 50.0);
      expect(metrics.isProfitable, true);
      expect(metrics.profitStatus, 'CALCULATED_CONFIRMED');
    });

    test('AIAnalysisModel.fromJson parses complete AI recommendation', () {
      final json = {
        'id': 55,
        'canonical_product_id': 10,
        'provider': 'openai',
        'model': 'gpt-4o-mini',
        'prompt_version': 'v1',
        'decision': 'BUY',
        'confidence': 0.88,
        'opportunity_score': 82.0,
        'risk_score': 24.5,
        'analysis': {
          'recommendation': {
            'decision': 'BUY',
            'confidence': 0.88,
            'summary': 'Excellent margin opportunity with low competition.',
          },
          'opportunity': {
            'score': 82.0,
            'level': 'HIGH',
            'justification': 'Strong spread between sourcing and selling price.',
          },
          'profitability': {
            'profit': '44.99',
            'margin': 22.5,
            'assessment': 'High net return.',
          },
          'competition': {
            'level': 'LOW',
            'score': 20.0,
            'summary': 'Few sellers offering same condition.',
          },
          'risk': {
            'level': 'LOW',
            'score': 24.5,
            'factors': ['Market price fluctuation'],
          },
          'reasons': ['High margin > 20%', 'Trusted seller on source market'],
          'warnings': ['Verify return policy'],
          'action_plan': ['Place initial trial order of 10 units', 'Monitor eBay selling speed'],
          'data_truth_summary': {
            'observed_data_available': true,
            'missing_critical_data': [],
          },
        },
        'deterministic_metrics': {
          'purchase_price': 120.00,
          'selling_price': 199.99,
          'shipping_cost': 15.00,
          'estimated_fees': 20.00,
          'other_costs': 0.00,
          'total_cost': 155.00,
          'estimated_profit': 44.99,
          'profit_margin_percent': 22.50,
          'return_on_investment_percent': 29.03,
          'is_profitable': true,
        },
        'duration_ms': 540,
        'tokens_used': 620,
      };

      final analysis = AIAnalysisModel.fromJson(json);
      expect(analysis.id, 55);
      expect(analysis.decision, 'BUY');
      expect(analysis.confidence, 0.88);
      expect(analysis.opportunityScore, 82.0);
      expect(analysis.riskScore, 24.5);
      expect(analysis.analysis.recommendation.decision, 'BUY');
      expect(analysis.analysis.reasons.length, 2);
      expect(analysis.analysis.actionPlan.length, 2);
      expect(analysis.deterministicMetrics.estimatedProfit, 44.99);
      expect(analysis.durationMs, 540.0);
      expect(analysis.tokensUsed, 620);
    });
  });
}
